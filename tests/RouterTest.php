<?php

declare(strict_types=1);

namespace Platine\Test\Route;

use InvalidArgumentException;
use Platine\Dev\PlatineTestCase;
use Platine\Http\ServerRequest;
use Platine\Http\Uri;
use Platine\Route\Exception\RouteNotFoundException;
use Platine\Route\Route;
use Platine\Route\RouteCollection;
use Platine\Route\Router;

/**
 * Router class tests
 *
 * @group core
 * @group route
 */
class RouterTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        //default
        $r = new Router();

        $this->assertInstanceOf(RouteCollection::class, $r->routes());

        //Using custom RouteCollection
        $rc = new RouteCollection();
        $r = new Router($rc);
        $this->assertInstanceOf(RouteCollection::class, $r->routes());
        $this->assertEquals($rc, $r->routes());
    }

    public function testBasePath(): void
    {
        //default
        $r = new Router();
        $this->assertEquals('/', $this->getPropertyValue(Router::class, $r, 'basePath'));
        $r->setBasePath('/foo');
        $this->assertEquals('/foo', $this->getPropertyValue(Router::class, $r, 'basePath'));
    }

    public function testGroup(): void
    {
        $r = new Router();

        $r->group('/foo', function ($r) {
            $r->add('/bar', 'handler', array('GET'), 'name');
        });

        $this->assertCount(1, $r->routes()->all());
        $route = $r->routes()->get('name');
        $this->assertEquals('/foo/bar', $route->getPattern());
    }

    public function testForm(): void
    {
        $r = new Router();

        $r->form('/foo', 'handler', 'name');
        $this->assertCount(1, $r->routes()->all());

        $route = $r->routes()->get('name');
        $this->assertEquals('/foo', $route->getPattern());
        $this->assertEquals('handler', $route->getHandler());
        $this->assertEquals('name', $route->getName());
        $this->assertCount(2, $route->getMethods());
        $this->assertContains('GET', $route->getMethods());
        $this->assertContains('POST', $route->getMethods());
    }

    public function testAdd(): void
    {
        $r = new Router();

        //Name is set
        $r->add('/foo', 'handler', array('GET'), 'name');
        $this->assertCount(1, $r->routes()->all());

        $route = $r->routes()->get('name');
        $this->assertEquals('/foo', $route->getPattern());
        $this->assertEquals('handler', $route->getHandler());
        $this->assertEquals('name', $route->getName());

        //Name is not set
        $r->add('/foo', 'handler', array('GET'));
        $this->assertCount(2, $r->routes()->all());

        $this->expectException(RouteNotFoundException::class);
        $route = $r->routes()->get('/foo');
    }

    public function testResource(): void
    {
        $r = new Router();

        //Name is set
        $r->resource('/user', 'handler', '');
        $this->assertCount(5, $r->routes()->all());

        $this->assertTrue($r->routes()->has('user_list'));
        $this->assertTrue($r->routes()->has('user_detail'));
        $this->assertTrue($r->routes()->has('user_create'));
        $this->assertTrue($r->routes()->has('user_update'));
        $this->assertTrue($r->routes()->has('user_delete'));

        $routeList = $r->routes()->get('user_list');
        $this->assertEquals('/user', $routeList->getPattern());
        $this->assertEquals('handler@index', $routeList->getHandler());
        $this->assertEquals('user_list', $routeList->getAttribute('permission'));
        $this->assertNull($routeList->getAttribute('csrf'));

        $routeDetail = $r->routes()->get('user_detail');
        $this->assertEquals('/user/detail/{id}', $routeDetail->getPattern());
        $this->assertEquals('handler@detail', $routeDetail->getHandler());
        $this->assertEquals('user_detail', $routeDetail->getAttribute('permission'));
        $this->assertNull($routeDetail->getAttribute('csrf'));

        $routeCreate = $r->routes()->get('user_create');
        $this->assertEquals('/user/create', $routeCreate->getPattern());
        $this->assertEquals('handler@create', $routeCreate->getHandler());
        $this->assertEquals('user_create', $routeCreate->getAttribute('permission'));
        $this->assertNull($routeCreate->getAttribute('csrf'));

        $routeUpdate = $r->routes()->get('user_update');
        $this->assertEquals('/user/update/{id}', $routeUpdate->getPattern());
        $this->assertEquals('handler@update', $routeUpdate->getHandler());
        $this->assertEquals('user_update', $routeUpdate->getAttribute('permission'));
        $this->assertNull($routeUpdate->getAttribute('csrf'));

        $routeDelete = $r->routes()->get('user_delete');
        $this->assertEquals('/user/delete/{id}', $routeDelete->getPattern());
        $this->assertEquals('handler@delete', $routeDelete->getHandler());
        $this->assertEquals('user_delete', $routeDelete->getAttribute('permission'));
        $this->assertTrue($routeDelete->getAttribute('csrf'));
    }

    /**
     * test custom route method
     *
     * @dataProvider routeMethodsDataProvider
     *
     * @param  string $func        method to execute on Router instance
     * @param  string $pattern        the route pattern
     * @param  mixed $handler        the route handle
     * @param  string $name           the name of the route
     * @param  string $requestMethod           the request method
     * @return void
     */
    public function testMethods(
        string $func,
        string $pattern,
        string $handler,
        string $name,
        string $requestMethod
    ): void {
        $r = new Router();

        $r->{$func}($pattern, $handler, $name);
        $this->assertCount(1, $r->routes()->all());

        $route = $r->routes()->get($name);
        $this->assertEquals($pattern, $route->getPattern());
        $this->assertEquals($handler, $route->getHandler());
        $this->assertEquals($name, $route->getName());
        if ($func !== 'any') {
            $this->assertContains($requestMethod, $route->getMethods());
        } else {
            $this->assertEmpty($route->getMethods());
        }
    }

    /**
     * test match method
     *
     * @dataProvider routeMatchDataProvider
     *
     * @param  string $pattern        the route pattern
     * @param  string $requestMethod           the request method
     * @param  string $path           the request URI path
     * @param  mixed $handler        the route handle
     * @param  string $name           the name of the route
     * @param  array $methods        the request methods allowed
     * @param  bool $checkAllowedMethods
     * @param  mixed $expectedResult
     * @return void
     */
    public function testMatch(
        $pattern,
        $requestMethod,
        $path,
        $handler,
        $name,
        array $methods,
        $checkAllowedMethods,
        $expectedResult
    ): void {
        $uri = $this->getMockBuilder(Uri::class)
                ->getMock();

        $uri->expects($this->any())
                ->method('getPath')
                ->will($this->returnValue($path));

        $serverRequest = $this->getMockBuilder(ServerRequest::class)
                ->getMock();

        $serverRequest->expects($this->any())
                ->method('getUri')
                ->will($this->returnValue($uri));

        $serverRequest->expects($this->any())
                ->method('getMethod')
                ->will($this->returnValue($requestMethod));

        $r = new Route($pattern, $handler, $name, $methods);

        $c = new RouteCollection();
        $c->add($r);

        $router = new Router($c);

        if ($expectedResult === null) {
            $this->assertNull($router->match($serverRequest, $checkAllowedMethods));
        } else {
            $this->assertEquals($r, $router->match($serverRequest, $checkAllowedMethods));
        }
    }

    /**
     * test path method
     *
     * @dataProvider routePathDataProvider
     *
     * @param  string $pattern        the route pattern
     * @param  mixed $handler        the route handle
     * @param  string $name        the route handle
     * @param  array $parameters        the method arguments parameters
     * @param  mixed $expectedResult
     * @return void
     */
    public function testPath(
        $pattern,
        $handler,
        $name,
        array $parameters,
        $expectedResult
    ): void {
        $r = new Route($pattern, $handler, $name);
        $c = new RouteCollection();
        $c->add($r);

        $router = new Router($c);

        if ($expectedResult == 'exception_notfound') {
            $this->expectException(RouteNotFoundException::class);
            $path = $router->path($name, $parameters);
        } elseif ($expectedResult == 'exception_invalid') {
            $this->expectException(InvalidArgumentException::class);
            $path = $router->path($name, $parameters);
        } else {
            $this->assertEquals(
                $expectedResult,
                $router->path($name, $parameters)
            );
        }
        $this->expectException(RouteNotFoundException::class);
        $path = $router->path('notfound_route_name', $parameters);
    }

    /**
     * Data provider for "testMethods"
     * @return array
     */
    public function routeMethodsDataProvider(): array
    {
        return array(
            array('get', '/foo', 'handler', 'name', 'GET'),
            array('post', '/foo', 'handler', 'name', 'POST'),
            array('put', '/foo', 'handler', 'name', 'PUT'),
            array('delete', '/foo', 'handler', 'name', 'DELETE'),
            array('patch', '/foo', 'handler', 'name', 'PATCH'),
            array('head', '/foo', 'handler', 'name', 'HEAD'),
            array('options', '/foo', 'handler', 'name', 'OPTIONS'),
            array('any', '/foo', 'handler', 'name', ''),
        );
    }

    /**
     * Data provider for "testMatch"
     * @return array
     */
    public function routeMatchDataProvider(): array
    {
        return array(
            array('/foo', 'GET', '/foo', 'handler', 'name', array('GET', 'POST'), true, 'route_instance'),
            array('/foo/{name}/{id:i}', 'GET', '/foo/bar/12', 'handler', 'name', array('GET', 'POST'), true, 'route_instance'),
            array('/foo', 'GET', '/foobar', 'handler', 'name', array('GET', 'POST'), true, null), //route not match
            array('/foo', 'PUT', '/foo', 'handler', 'name', array('GET', 'POST'), true, null), //method not match
        );
    }

    /**
     * Data provider for "testPath"
     * @return array
     */
    public function routePathDataProvider(): array
    {
        return array(
            array('/foo', 'handler', 'myname', [], '/foo'),
            array('/foo/{name}', 'handler', 'foobar', [], 'exception_invalid'),
            array('/foo/{id}', 'handler', 'name', array('id' => 15), '/foo/15'),
            array('/foo/{id}/{name}', 'handler', 'baz', array('id' => 60, 'name' => 'foobar'), '/foo/60/foobar'),
        );
    }
}
