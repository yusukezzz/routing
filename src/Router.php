<?php namespace yusukezzz\Routing;

class Router
{
    /** @var RouteParser */
    protected $parser;
    /** @var RouteDataGenerator */
    protected $generator;
    /** @var RouteHandler */
    protected $handler;

    /**
     * Current matched route instance
     *
     * @var Route
     */
    protected $currentRoute;

    /**
     * Cached results of urlFor method
     *
     * @var array
     */
    protected $generatedUrls = [];

    public function __construct(RouteHandlerInterface $handler = null, RouteDataGenerator $generator = null)
    {
        $this->parser = new RouteParser;
        $this->handler = $handler ?: new RouteHandler();
        $this->generator = $generator ?: new RouteDataGenerator();
    }

    /**
     * Adds a route to the collection.
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
        $this->addRoute('GET', $uri, $handler, $name);
    }

    public function head($uri, $handler, $name = null)
    {
        $this->addRoute('HEAD', $uri, $handler, $name);
    }

    public function post($uri, $handler, $name = null)
    {
        $this->addRoute('POST', $uri, $handler, $name);
    }

    public function put($uri, $handler, $name = null)
    {
        $this->addRoute('PUT', $uri, $handler, $name);
    }

    public function delete($uri, $handler, $name = null)
    {
        $this->addRoute('DELETE', $uri, $handler, $name);
    }

    public function options($uri, $handler, $name = null)
    {
        $this->addRoute('OPTIONS', $uri, $handler, $name);
    }

    /**
     * Handle request
     *
     * @param string $httpMethod
     * @param string $uri
     * @return mixed
     */
    public function handle($httpMethod, $uri)
    {
        $matcher = new RouteMatcher($this->generator->getData(), $this->handler);
        $this->currentRoute = $route = $matcher->match($httpMethod, $uri);

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
    public function urlFor($name, array $params = [])
    {
        $cacheKey = md5($name . json_encode(ksort($params)));
        if (array_key_exists($cacheKey, $this->generatedUrls)) {
            return $this->generatedUrls[$cacheKey];
        }

        $route = $this->generator->getNamedRoute($name);
        $url = $this->buildUrl($route, $params);
        $this->generatedUrls[$cacheKey] = $url;

        return $url;
    }

    /**
     * @param Route $route
     * @param array $params
     * @return string
     */
    protected function buildUrl(Route $route, array $params)
    {
        $targets = [];
        $replacements = [];
        foreach ($route->getVariableNames() as $key) {
            if ( ! isset($params[$key])) {
                throw new \InvalidArgumentException("Variable '{$key}' not found or null for route '{$route->getName()}'");
            }
            $targets[] = ":" . $key;
            $replacements[] = $params[$key];
        }

        return str_replace($targets, $replacements, $route->getUri());
    }
}