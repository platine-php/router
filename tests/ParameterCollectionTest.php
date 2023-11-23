<?php

declare(strict_types=1);

namespace Platine\Test\Route;

use Platine\Route\Parameter;
use Platine\Route\ParameterCollection;
use Platine\Dev\PlatineTestCase;

/**
 * ParameterCollection class tests
 *
 * @group core
 * @group route
 */
class ParameterCollectionTest extends PlatineTestCase
{
    public function testConstructorOneValueIsNotInstanceOfParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ParameterCollection(array('foo')));
    }

    public function testConstructorParamContainsListOfParameter(): void
    {
        $name = 'foo';
        $value = 'bar';
        $c = new ParameterCollection(array(new Parameter($name, $value)));
        $this->assertCount(1, $c->all());
        $this->assertTrue($c->has($name));
    }

    public function testGetAndDelete(): void
    {
        $name = 'foo';
        $value = 'bar';
        $c = new ParameterCollection(array(new Parameter($name, $value)));
        $this->assertCount(1, $c->all());
        $this->assertTrue($c->has($name));
        $this->assertInstanceOf(Parameter::class, $c->get($name));
        $this->assertEquals('bar', $c->get($name)->getValue());
        $c->delete($name);
        $this->assertFalse($c->has($name));
        //TODO: when delete an parameter only is deleted from list not from all
        $this->assertCount(1, $c->all());
    }
}
