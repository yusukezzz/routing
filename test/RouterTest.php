<?php

class RouterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \yusukezzz\Routing\Router
     */
    protected $router;

    protected function setUp()
    {
        $this->router = new \yusukezzz\Routing\Router();
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
        $this->router->get('/hoge/:var1/:var2', function($var1, $var2)
        {
            return "got {$var1} and {$var2}";
        });
        $var1 = 'huga';
        $var2 = 'piyo';
        $res = $this->router->handle('GET', "/hoge/{$var1}/{$var2}");

        $this->assertSame("got {$var1} and {$var2}", $res);
    }

    /**
     * @expectedException \yusukezzz\Routing\Exceptions\NotFoundException
     * @expectedExceptionMessage Route '/hoge' not found
     */
    public function test_handle_not_found()
    {
        $this->router->handle('GET', '/hoge');
    }

    /**
     * @expectedException \yusukezzz\Routing\Exceptions\MethodNotAllowedException
     * @expectedExceptionMessage Allow GET,POST
     */
    public function test_handle_methods_not_allowed()
    {
        $uri = '/hoge';
        $this->router->get($uri, 'handle');
        $this->router->post($uri, 'handle');
        $this->router->handle('PUT', $uri);
    }

    /**
     * @dataProvider urlForDataProvider
     */
    public function test_urlFor($name, $uri, $params, $expected)
    {
        $this->router->get($uri, 'handler', $name);
        $actual = $this->router->urlFor($name, $params);
        $this->assertSame($expected, $actual);
    }

    public static function urlForDataProvider()
    {
        return [
            ['static_only', '/hoge/huga/piyo', [], '/hoge/huga/piyo'],
            ['variable_only', '/:string/:id', ['string' => 'aiueo', 'id' => 123], '/aiueo/123'],
            ['static_variable', '/hoge/:string', ['string' => 'aiueo'], '/hoge/aiueo'],
            ['static_variable_static', '/hoge/:string/static', ['string' => 'aiueo'], '/hoge/aiueo/static'],
            ['variable_static', '/:string/hoge', ['string' => 'aiueo'], '/aiueo/hoge'],
        ];
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
        $this->router->get('/:param', 'handler', $name);
        $this->router->urlFor($name, []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Variable 'param' not found or null for route 'null_parameter'
     */
    public function test_urlFor_null_parameter()
    {
        $name = 'null_parameter';
        $this->router->get('/:param', 'handler', $name);
        $this->router->urlFor($name, ['param' => null]);
    }
}