<?php

namespace FastRoute;

use FastRoute\Exceptions\MethodNotAllowedException;
use FastRoute\Exceptions\NotFoundException;

class Dispatcher
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
     */
    public function dispatch($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri])) {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }

        $varRouteData = $this->variableRouteData;
        if (isset($varRouteData[$httpMethod])) {
            $route = $this->dispatchVariableRoute($varRouteData[$httpMethod], $uri);
            if ($route) {
                return $route;
            }
        } else if ($httpMethod === 'HEAD' && isset($varRouteData['GET'])) {
            $route = $this->dispatchVariableRoute($varRouteData['GET'], $uri);
            if ($route) {
                return $route;
            }
        }

        $this->findAllowedMethods($httpMethod, $uri, $varRouteData);
    }

    protected function findAllowedMethods($httpMethod, $uri, $varRouteData)
    {
        $allowedMethods = [];
        foreach ($varRouteData as $method => $routeData) {
            if ($method === $httpMethod) {
                continue;
            }

            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result) {
                $allowedMethods[] = $method;
            }
        }

        // If there are no allowed methods the route simply does not exist
        if ($allowedMethods) {
            $this->handleMethodNotAllowed($allowedMethods);
        } else {
            $this->handleNotFound($uri);
        }
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     * @return Route
     * @throws MethodNotAllowedException
     */
    protected function dispatchStaticRoute($httpMethod, $uri)
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
     * @throws NotFoundException
     */
    protected function dispatchVariableRoute($routeData, $uri)
    {
        foreach ($routeData as $data) {
            if (!preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            /** @var Route $route */
            $route = $data['routeMap'][count($matches)];

            $vars = [];
            $i = 0;
            foreach ($route->variableNames() as $varName) {
                $vars[$varName] = $matches[++$i];
            }
            $route->setVariables($vars);
            return $route;
        }

        return null;
    }

    /**
     * @param $uri
     * @throws NotFoundException
     */
    protected function handleNotFound($uri)
    {
        throw new NotFoundException("Route '{$uri}' not found");
    }

    /**
     * @param $allowed
     * @throws MethodNotAllowedException
     */
    protected function handleMethodNotAllowed($allowed)
    {
        throw new MethodNotAllowedException('Allow ' . implode(',', $allowed));
    }
}