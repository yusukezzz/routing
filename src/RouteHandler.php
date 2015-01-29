<?php

namespace FastRoute;

class RouteHandler implements RouteHandlerInterface
{
    /**
     * @param Route $route
     * @return mixed
     */
    public function handleRoute(Route $route)
    {
        return call_user_func_array($route->handler(), $route->variables());
    }
}