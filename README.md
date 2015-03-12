yusukezzz\Routing - Simple and fast request routing for PHP
=======================================

This library forked from [nikic/FastRoute](https://github.com/nikic/FastRoute).

Very thanks to nikic and other contributors of FastRoute.

Install
-------

Require this package in your composer.json

```
"yusukezzz/routing": "dev-master"
```

Usage
-----

```php
<?php
require_once __DIR__ . '/path/to/vendor/autoload.php';

$router = new \yusukezzz\Routing\Router();

$router->get('/', function()
{
    return 'Hello world.';
});

$router->get('/user/:name', function($name)
{
    return "Hello {$name}";
});

echo $router->handle($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```

run in PHP builtin server

```
php -S localhost:8080 router.php
```

Reverse routing

```php
// set third parameter to define named route
$router->get('/hoge/:piyo', function($piyo){}, 'a_named_route');
echo $router->urlFor('a_named_route', ['piyo' => 'abc']);
// output is '/hoge/abc'
```

If you want to use Controller like routing, you should implement RouteHandlerInterface.

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

class PhotoController
{
    public function index()
    {
        return 'photo list';
    }

    public function show($id)
    {
        return "show photo id {$id}";
    }
}

class Handler implements \yusukezzz\Routing\RouteHandlerInterface
{
    /**
     * @param \yusukezzz\Routing\Route $route
     * @return mixed
     */
    public function handleRoute(\yusukezzz\Routing\Route $route)
    {
        list($controller, $method) = explode('@', $route->getHandler());
        $handler = [new $controller, $method];

        return call_user_func_array($handler, $route->getVariables());
    }
}

$handler = new \Handler();
$router = new \yusukezzz\Routing\Router($handler);

$router->get('/photo', 'PhotoController@index');
$router->get('/photo/:id', 'PhotoController@show');


echo $router->handle($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```