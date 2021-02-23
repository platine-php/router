<?php

declare(strict_types=1);

namespace Platine\Test\Route;

use Platine\Route\Router;
use Platine\Route\Route;
use Platine\Route\RouteCollection;
use Platine\Route\Exception\RouteNotFoundException;
use Platine\Http\ServerRequest;
use Platine\Http\Uri;
use Platine\PlatineTestCase;

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
            $this->expectException(\InvalidArgumentException::class);
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
            array('/foo/{name}', 'GET', '/foo/bar', 'handler', 'name', array('GET', 'POST'), true, 'route_instance'),
            array('/foo/{name}/{id:i}', 'GET', '/foo/bar/12', 'handler', 'name', array('GET', 'POST'), true, 'route_instance'),
            array('/foo{name}?', 'GET', '/foo', 'handler', 'name', array('GET', 'POST'), true, 'route_instance'),
            array('/foo/{name}?', 'GET', '/foo/baz', 'handler', 'name', array('GET', 'POST'), true, 'route_instance'),
            array('/foo', 'GET', '/foobar', 'handler', 'name', array('GET', 'POST'), true, null), //route not match
            array('/foo', 'PUT', '/foo', 'handler', 'name', array('GET', 'POST'), true, null), //method not match
            array('/foo', 'PUT', '/foo', 'handler', 'name', array('GET', 'POST'), false, 'route_instance'), //method not match but don't check
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
