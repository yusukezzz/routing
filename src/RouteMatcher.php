<?php namespace yusukezzz\Routing;

use yusukezzz\Routing\Exceptions\MethodNotAllowedException;
use yusukezzz\Routing\Exceptions\NotFoundException;

class RouteMatcher
{
    /** @var array */
    protected $staticRouteMap;
    /** @var array */
    protected $variableRouteData;

    public function __construct($routeData)
    {
        list($this->staticRouteMap, $this->variableRouteData) = $routeData;
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     * @return Route
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function match($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri])) {
            return $this->matchStaticRoute($httpMethod, $uri);
        }

        $route = null;
        $varRouteData = $this->variableRouteData;
        if (isset($varRouteData[$httpMethod])) {
            $route = $this->matchVariableRoute($varRouteData[$httpMethod], $uri);
        } elseif ($httpMethod === 'HEAD' && isset($varRouteData['GET'])) {
            $route = $this->matchVariableRoute($varRouteData['GET'], $uri);
        }

        // variable route matched
        if ($route) {
            return $route;
        }

        $allowedMethods = $this->findAllowedMethods($httpMethod, $uri, $varRouteData);

        if ($allowedMethods) {
            $this->handleMethodNotAllowed($allowedMethods);
        }

        // If there are no allowed methods the route simply does not exist
        $this->handleNotFound($uri);
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     * @param array $varRouteData
     * @return array
     */
    protected function findAllowedMethods($httpMethod, $uri, $varRouteData)
    {
        $allowedMethods = [];
        foreach ($varRouteData as $method => $routeData) {
            if ($method === $httpMethod) {
                continue;
            }

            $result = $this->matchVariableRoute($routeData, $uri);
            if ($result) {
                $allowedMethods[] = $method;
            }
        }

        return $allowedMethods;
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     * @return Route
     */
    protected function matchStaticRoute($httpMethod, $uri)
    {
        $routes = $this->staticRouteMap[$uri];

        if (isset($routes[$httpMethod])) {
            return $routes[$httpMethod];
        } elseif ($httpMethod === 'HEAD' && isset($routes['GET'])) {
            return $routes['GET'];
        } else {
            $this->handleMethodNotAllowed(array_keys($routes));
        }
    }

    /**
     * @param array $routeData
     * @param string $uri
     * @return Route|null
     */
    protected function matchVariableRoute($routeData, $uri)
    {
        $route = null;
        foreach ($routeData as $data) {
            if ( ! preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            /** @var Route $route */
            $route = $data['routeMap'][count($matches)];

            $vars = [];
            $i = 0;
            foreach ($route->getVariableNames() as $varName) {
                $vars[$varName] = $matches[++$i];
            }
            $route->setVariables($vars);
            break;
        }

        return $route;
    }

    /**
     * @param string $uri
     * @throws NotFoundException
     */
    protected function handleNotFound($uri)
    {
        throw new NotFoundException("Route '{$uri}' not found");
    }

    /**
     * @param array $allowed
     * @throws MethodNotAllowedException
     */
    protected function handleMethodNotAllowed($allowed)
    {
        throw new MethodNotAllowedException('Allow ' . implode(',', $allowed));
    }
}