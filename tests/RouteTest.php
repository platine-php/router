<?php

declare(strict_types=1);

namespace Platine\Test\Route;

use Platine\Route\Route;
use Platine\Route\Exception\InvalidRouteParameterException;
use Platine\Http\ServerRequest;
use Platine\Http\Uri;
use Platine\PlatineTestCase;

/**
 * Route class tests
 *
 * @group core
 * @group route
 */
class RouteTest extends PlatineTestCase
{

    public function testConstructor(): void
    {
        $r = new Route('pattern', 'handler', 'name');
        $this->assertEquals('name', $r->getName());
        $this->assertEquals('pattern', $r->getPattern());
        $this->assertEquals('handler', $r->getHandler());
        $this->assertEmpty($r->getMethods());

        //When methods is set
        $r = new Route('pattern', 'handler', 'name', array('get', 'put'));
        $this->assertNotEmpty($r->getMethods());
        $this->assertContains('GET', $r->getMethods());
        $this->assertContains('PUT', $r->getMethods());

        //Invalid method
        $this->expectException(InvalidRouteParameterException::class);
        $r = new Route('pattern', 'handler', 'name', array('get', 'put', 34));
    }

    public function testSetGetName(): void
    {
        $r = new Route('pattern', 'handler', 'name');
        $this->assertEquals('name', $r->getName());

        $r->setName('foo_name');
        $this->assertEquals('foo_name', $r->getName());
    }

    public function testIsAllowedMethod(): void
    {
        $r = new Route('pattern', 'handler', 'name');
        $this->assertEmpty($r->getMethods());
        $this->assertTrue($r->isAllowedMethod('GET'));
        $this->assertTrue($r->isAllowedMethod('POST'));

        //When methods is set
        $r = new Route('pattern', 'handler', 'name', array('get', 'put'));
        $this->assertTrue($r->isAllowedMethod('GET'));
        $this->assertTrue($r->isAllowedMethod('PUT'));
        $this->assertFalse($r->isAllowedMethod('POST'));
    }

    /**
     * test match method
     *
     * @dataProvider routeMatchDataProvider
     *
     * @param  string $pattern        the route pattern
     * @param  string $path           the request URI path
     * @param  mixed $handler        the route handle
     * @param  string $name           the name of the route
     * @param  array $methods        the request methods allowed
     * @param  array $parameters        the matched parameters if exist
     * @param  mixed $expectedResult
     * @return void
     */
    public function testMatch(
        $pattern,
        $path,
        $handler,
        $name,
        array $methods,
        array $parameters,
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

        $r = new Route($pattern, $handler, $name, $methods);

        $this->assertEquals($name, $r->getName());
        $this->assertEquals($pattern, $r->getPattern());
        $this->assertEquals($handler, $r->getHandler());
        $this->assertEquals(count($methods), count($r->getMethods()));

        $this->assertEquals($expectedResult, $r->match($serverRequest));

        if (!empty($parameters)) {
            $params = $r->getParameters()->all();
            $list = [];

            foreach ($params as $parameter) {
                $list[$parameter->getName()] = $parameter->getValue();
            }

            foreach ($parameters as $name => $value) {
                $this->assertArrayHasKey($name, $list);
                $this->assertEquals($value, $list[$name]);
            }
        }
    }

    /**
     * test path method
     *
     * @dataProvider routePathDataProvider
     *
     * @param  string $pattern        the route pattern
     * @param  mixed $handler        the route handle
     * @param  array $parameters        the method arguments parameters
     * @param  mixed $expectedResult
     * @return void
     */
    public function testPath($pattern, $handler, array $parameters, $expectedResult): void
    {
        $r = new Route($pattern, $handler);

        if ($expectedResult == 'exception') {
            $this->expectException(\InvalidArgumentException::class);
            $path = $r->path($parameters);
        } else {
            $this->assertEquals($expectedResult, $r->path($parameters));
        }
    }

    /**
     * Data provider for "testMatch"
     * @return array
     */
    public function routeMatchDataProvider(): array
    {
        return array(
            array('/foo', '/foobar', 'handler', 'name', [], [], false),
            array('/foo/{id}', '/foo', 'handler', 'name', [], [], false),
            array('/foo/{id}', '/foo/34', 'handler', 'name', [], array('id' => 34), true),
        );
    }

    /**
     * Data provider for "testPath"
     * @return array
     */
    public function routePathDataProvider(): array
    {
        return array(
            array('/foo', 'handler', [], '/foo'),
            array('/foo/{id}', 'handler', array('id' => 60), '/foo/60'),
            array('/foo/{id}/{name:a}', 'handler', array('id' => 60, 'name' => 'abc'), '/foo/60/abc'),
            array('/foo/{id}/{name}', 'handler', array('id' => 60), 'exception'),
        );
    }
}
