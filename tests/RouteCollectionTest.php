<?php

declare(strict_types=1);

namespace Platine\Test\Route;

use Platine\Route\Route;
use Platine\Route\RouteCollection;
use Platine\Route\Exception\RouteNotFoundException;
use Platine\Route\Exception\RouteAlreadyExistsException;
use Platine\Dev\PlatineTestCase;

/**
 * RouteCollection class tests
 *
 * @group core
 * @group route
 */
class RouteCollectionTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        //Default
        $c = new RouteCollection();
        $this->assertEmpty($c->all());
        $this->assertCount(0, $c->all());

        $c = new RouteCollection(array(new Route('pattern', 'handler', 'name')));
        $this->assertCount(1, $c->all());

        //Value is not an instance of RouteCollectionInterface
        $this->expectException(\InvalidArgumentException::class);
        $c = new RouteCollection(array(new Route('pattern', 'handler', 'name'), 123));
    }

    public function testAdd(): void
    {
        $r = new Route('pattern', 'handler', 'name');
        $c = new RouteCollection();
        $this->assertEmpty($c->all());

        $c->add($r);

        $this->assertCount(1, $c->all());

        //Route Already exists
        $this->expectException(RouteAlreadyExistsException::class);
        $c->add($r);
    }

    public function testGet(): void
    {
        $r = new Route('pattern', 'handler', 'name');
        $c = new RouteCollection();

        $c->add($r);

        $this->assertEquals($r, $c->get('name'));

        //Route Not found exists
        $this->expectException(RouteNotFoundException::class);
        $c->get('not found route');
    }

    public function testHas(): void
    {
        $r = new Route('pattern', 'handler', 'name');
        $c = new RouteCollection();

        $c->add($r);

        $this->assertTrue($c->has('name'));
        $this->assertFalse($c->has('not found route'));
    }

    public function testRemove(): void
    {
        $r = new Route('pattern', 'handler', 'name');
        $c = new RouteCollection();
        $this->assertEmpty($c->all());

        $c->add($r);

        $this->assertCount(1, $c->all());

        $this->assertEquals($r, $c->remove('name'));

        $this->assertCount(1, $c->all());

        //Route Not found exists
        $this->expectException(RouteNotFoundException::class);
        $c->remove('not found route');
    }

    public function testClear(): void
    {
        $r = new Route('pattern', 'handler', 'name');
        $c = new RouteCollection();
        $this->assertEmpty($c->all());

        $c->add($r);

        $this->assertCount(1, $c->all());

        $c->clear();

        $this->assertCount(0, $c->all());
    }
}
