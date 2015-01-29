<?php

namespace FastRoute;

class Router
{
    /** @var RouteParser */
    protected $parser;
    /** @var RouteDataGenerator */
    protected $generator;
    /** @var RouteHandler */
    protected $handler;
    /** @var Route */
    protected $currentRoute = null;

    public function __construct(RouteHandlerInterface $resolver = null)
    {
        $this->parser = new RouteParser;
        $this->generator = new RouteDataGenerator();
        $this->handler = $resolver ?: new RouteHandler();
    }

    /**
     * Adds a route to the collection.
     *
     * The syntax used in the $route string depends on the used route parser.
     *
     * @param string $httpMethod
     * @param string $uri
     * @param mixed  $handler
     * @param string $name
     */
    public function addRoute($httpMethod, $uri, $handler, $name)
    {
        $routeData = $this->parser->parse($uri);
        $this->generator->addRoute($httpMethod, $routeData, $uri, $handler, $name);
    }

    public function get($uri, $handler, $name = null)
    {
        $this->addRoute(Route::GET, $uri, $handler, $name);
    }

    public function head($uri, $handler, $name = null)
    {
        $this->addRoute(Route::HEAD, $uri, $handler, $name);
    }

    public function post($uri, $handler, $name = null)
    {
        $this->addRoute(Route::POST, $uri, $handler, $name);
    }

    public function put($uri, $handler, $name = null)
    {
        $this->addRoute(Route::PUT, $uri, $handler, $name);
    }

    public function delete($uri, $handler, $name = null)
    {
        $this->addRoute(Route::DELETE, $uri, $handler, $name);
    }

    public function options($uri, $handler, $name = null)
    {
        $this->addRoute(Route::OPTIONS, $uri, $handler, $name);
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     * @return mixed
     */
    public function handle($httpMethod, $uri)
    {
        $dispatcher = new Dispatcher($this->generator->getData(), $this->handler);
        $this->currentRoute = $route = $dispatcher->dispatch($httpMethod, $uri);

        return $this->handler->handleRoute($route);
    }

    /**
     * Return current route if matched
     *
     * @return Route|null
     */
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    /**
     * Generate named route URL
     *
     * @param string $name
     * @param array $params
     * @throws \InvalidArgumentException
     * @return string
     */
    public function urlFor($name, $params = [])
    {
        $route = $this->generator->getNamedRoute($name);
        if (is_null($route)) {
            throw new \RuntimeException("Named route '{$name}' not found");
        }

        $variables = [];
        foreach ($route->variableNames() as $key) {
            if ( ! isset($params[$key])) {
                throw new \InvalidArgumentException("Variable '{$key}' not found or null for route '{$name}'");
            }
            $variables[] = '#\{' . $key . ':?.*?\}#';
        }

        return preg_replace($variables, $params, $route->uri());
    }
}