<?php namespace yusukezzz\Routing;

interface RouteHandlerInterface
{
    /**
     * @param Route $route
     * @return mixed
     */
    public function handleRoute(Route $route);
}