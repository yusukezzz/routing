<?php

class RouterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \FastRoute\Router
     */
    protected $router;

    protected function setUp()
    {
        $this->router = new \FastRoute\Router();
    }

    protected function tearDown()
    {
        $this->router = null;
    }

    public function test_addRoute_static_routing()
    {
        $uri = '/hoge';
        $this->router->get($uri, function()
        {
            return 'hello';
        });
        $res = $this->router->handle('GET', $uri);

        $this->assertSame('hello', $res);
    }

    public function test_addRoute_variable_routing()
    {
        $this->router->get('/hoge/{var1}/{var2}', function($var1, $var2)
        {
            return "got {$var1} and {$var2}";
        });
        $var1 = 'huga';
        $var2 = 'piyo';
        $res = $this->router->handle('GET', "/hoge/{$var1}/{$var2}");

        $this->assertSame("got {$var1} and {$var2}", $res);
    }

    /**
     * @expectedException \FastRoute\Exceptions\NotFoundException
     * @expectedExceptionMessage Route '/hoge' not found
     */
    public function test_handle_not_found()
    {
        $this->router->handle('GET', '/hoge');
    }

    /**
     * @expectedException \FastRoute\Exceptions\MethodNotAllowedException
     * @expectedExceptionMessage Allow GET,POST
     */
    public function test_handle_methods_not_allowed()
    {
        $uri = '/hoge';
        $this->router->get($uri, 'handle');
        $this->router->post($uri, 'handle');
        $this->router->handle('PUT', $uri);
    }

    public function test_urlFor_static_routing()
    {
        $name = 'static_route';
        $this->router->get('/hoge/huga/piyo', 'handler', $name);
        $res = $this->router->urlFor($name);

        $this->assertSame('/hoge/huga/piyo', $res);
    }

    public function test_urlFor_variable_routing()
    {
        $name = 'variable_route';
        $this->router->get('/hoge/{string}/{id}', 'handler', $name);
        $res = $this->router->urlFor($name, ['string' => 'aiueo', 'id' => 123]);

        $this->assertSame('/hoge/aiueo/123', $res);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Named route 'unknown' not found
     */
    public function test_urlFor_unknown_route()
    {
        $this->router->urlFor('unknown');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Variable 'param' not found or null for route 'nothing_parameter'
     */
    public function test_urlFor_nothing_parameter()
    {
        $name = 'nothing_parameter';
        $this->router->get('/{param}', 'handler', $name);
        $this->router->urlFor($name, []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Variable 'param' not found or null for route 'null_parameter'
     */
    public function test_urlFor_null_parameter()
    {
        $name = 'null_parameter';
        $this->router->get('/{param}', 'handler', $name);
        $this->router->urlFor($name, ['param' => null]);
    }
}