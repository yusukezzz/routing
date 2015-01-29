<?php

namespace FastRoute;

class Route
{
    const GET = 'GET';
    const HEAD = 'HEAD';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const OPTIONS = 'OPTIONS';

    /** @var string */
    protected $httpMethod;
    /** @var string */
    protected $regex;
    /** @var array */
    protected $variable_names;
    /** @var array */
    protected $variables = [];
    /** @var mixed */
    protected $handler;
    /** @var string */
    protected $uri;
    /** @var string */
    protected $name;

    /**
     * @param string $httpMethod
     * @param string $uri
     * @param mixed  $handler
     * @param string $regex
     * @param array $variable_names
     * @param string $name
     */
    public function __construct($httpMethod, $uri, $handler, $regex, $variable_names, $name = null)
    {
        $this->httpMethod = $httpMethod;
        $this->uri = $uri;
        $this->handler = $handler;
        $this->regex = $regex;
        $this->variable_names = $variable_names;
        $this->name = $name;
    }

    public function httpMethod()
    {
        return $this->httpMethod;
    }

    public function regex()
    {
        return $this->regex;
    }

    public function variableNames()
    {
        return $this->variable_names;
    }

    public function variables()
    {
        return $this->variables;
    }

    public function setVariables($variables)
    {
        $this->variables = $variables;
    }

    public function handler()
    {
        return $this->handler;
    }

    public function uri()
    {
        return $this->uri;
    }

    public function name()
    {
        return $this->name;
    }

    /**
     * Tests whether this route matches the given string.
     *
     * @param string $str
     *
     * @return bool
     */
    public function matches($str)
    {
        $regex = '~^' . $this->regex . '$~';
        return (bool) preg_match($regex, $str);
    }
}