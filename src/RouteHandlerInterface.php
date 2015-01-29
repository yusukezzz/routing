<?php

namespace FastRoute;

interface RouteHandlerInterface
{
    /**
     * @param Route $route
     * @return mixed
     */
    public function handleRoute(Route $route);
}