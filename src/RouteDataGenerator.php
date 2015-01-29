<?php

namespace FastRoute;

use FastRoute\Exceptions\BadRouteException;

class RouteDataGenerator
{
    const APPROX_CHUNK_SIZE = 10;

    /** @var array */
    protected $staticRoutes = [];
    /** @var array */
    protected $methodToRegexToRoutesMap = [];
    /** @var array */
    protected $namedRoutes = [];

    /**
     * @param string $httpMethod
     * @param array $routeData
     * @param string $pattern
     * @param callable $handler
     * @param string $name
     */
    public function addRoute($httpMethod, $routeData, $pattern, $handler, $name = null)
    {
        if ($this->isStaticRoute($routeData)) {
            $route = $this->addStaticRoute($httpMethod, $routeData, $pattern, $handler, $name);
        } else {
            $route = $this->addVariableRoute($httpMethod, $routeData, $pattern, $handler, $name);
        }

        if ( ! is_null($name)) {
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * @param string $name
     * @return Route
     */
    public function getNamedRoute($name)
    {
        if (isset($this->namedRoutes[$name])) {
            return $this->namedRoutes[$name];
        } else {
            return null;
        }
    }

    public function getData()
    {
        if (empty($this->methodToRegexToRoutesMap)) {
            return [$this->staticRoutes, []];
        }

        return [$this->staticRoutes, $this->generateVariableRouteData()];
    }

    /**
     * @param Route[] $regexToRoutesMap
     * @return array
     */
    protected function processChunk($regexToRoutesMap)
    {
        $routeMap = [];
        $regex_list = [];
        $numGroups = 0;
        foreach ($regexToRoutesMap as $regex => $route) {
            $numVariables = count($route->variableNames());
            $numGroups = max($numGroups, $numVariables);

            $regex_list[] = $regex . str_repeat('()', $numGroups - $numVariables);
            $routeMap[$numGroups + 1] = $route;

            ++$numGroups;
        }

        $regex = '~^(?|' . implode('|', $regex_list) . ')$~';
        return ['regex' => $regex, 'routeMap' => $routeMap];
    }

    protected function generateVariableRouteData()
    {
        $data = [];
        foreach ($this->methodToRegexToRoutesMap as $method => $regexToRoutesMap) {
            $chunkSize = $this->computeChunkSize(count($regexToRoutesMap));
            $chunks = array_chunk($regexToRoutesMap, $chunkSize, true);
            $data[$method] =  array_map([$this, 'processChunk'], $chunks);
        }
        return $data;
    }

    protected function computeChunkSize($count)
    {
        $numParts = max(1, round($count / self::APPROX_CHUNK_SIZE));
        return ceil($count / $numParts);
    }

    protected function isStaticRoute($routeData)
    {
        return count($routeData) === 1 && is_string($routeData[0]);
    }

    protected function addStaticRoute($httpMethod, $routeData, $pattern, $handler, $name)
    {
        $routeStr = $routeData[0];

        if (isset($this->staticRoutes[$routeStr][$httpMethod])) {
            throw new BadRouteException(sprintf(
                'Cannot register two routes matching "%s" for method "%s"',
                $routeStr, $httpMethod
            ));
        }

        if (isset($this->methodToRegexToRoutesMap[$httpMethod])) {
            foreach ($this->methodToRegexToRoutesMap[$httpMethod] as $route) {
                /** @var Route $route */
                if ($route->matches($routeStr)) {
                    throw new BadRouteException(sprintf(
                        'Static route "%s" is shadowed by previously defined variable route "%s" for method "%s"',
                        $routeStr, $route->regex(), $httpMethod
                    ));
                }
            }
        }

        $regex = '';
        $variables = [];
        $route = new Route($httpMethod, $pattern, $handler, $regex, $variables, $name);

        return $this->staticRoutes[$routeStr][$httpMethod] = $route;
    }

    protected function addVariableRoute($httpMethod, $routeData, $pattern, $handler, $name)
    {
        list($regex, $variables) = $this->buildRegexForRoute($routeData);

        if (isset($this->methodToRegexToRoutesMap[$httpMethod][$regex])) {
            throw new BadRouteException(sprintf(
                'Cannot register two routes matching "%s" for method "%s"',
                $regex, $httpMethod
            ));
        }

        $route = new Route($httpMethod, $pattern, $handler, $regex, $variables, $name);

        return $this->methodToRegexToRoutesMap[$httpMethod][$regex] = $route;
    }

    protected function buildRegexForRoute($routeData)
    {
        $regex = '';
        $variables = [];
        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            list($varName, $regexPart) = $part;

            if (isset($variables[$varName])) {
                throw new BadRouteException(sprintf(
                    'Cannot use the same placeholder "%s" twice', $varName
                ));
            }

            $variables[$varName] = $varName;
            $regex .= '(' . $regexPart . ')';
        }

        return [$regex, $variables];
    }
}