<?php

declare(strict_types=1);

namespace Platine\Test\Route;

use Platine\Route\Parameter;
use Platine\Dev\PlatineTestCase;

/**
 * Parameter class tests
 *
 * @group core
 * @group route
 */
class ParameterTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        $name = 'foo';
        $value = 'bar';
        $c = new Parameter($name, $value);
        $this->assertEquals($name, $c->getName());
        $this->assertEquals($value, $c->getValue());
    }

    public function testSetGetParameterValue(): void
    {
        $name = 'foo';
        $value = 'bar';
        $c = new Parameter($name, $value);
        $this->assertEquals($name, $c->getName());
        $this->assertEquals($value, $c->getValue());

        $c->setValue('foo');
        $this->assertEquals($name, $c->getName());
        $this->assertEquals('foo', $c->getValue());
    }
}
