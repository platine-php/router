<?php

/**
 * Platine Router
 *
 * Platine Router is the a lightweight and simple router using middleware
 *  to match and dispatch the request.
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2020 Platine Router
 * Copyright (c) 2020 Evgeniy Zyubin
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 *  @file ParameterCollection.php
 *
 *  This class represent the collection of matched route parameters
 *
 *  @package    Platine\Route
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Route;

/**
 * @class ParameterCollection
 * @package Platine\Route
 */
class ParameterCollection
{
    /**
     * The array of parameters
     * @var array<string, ParameterInterface>
     */
    protected array $parameters = [];

    /**
     * The array of all of the route parameters
     * @var ParameterInterface[]
     */
    protected array $all = [];

    /**
     * Create new collection of parameters
     *
     * @param ParameterInterface[] $parameters  the route parameters
     */
    public function __construct(array $parameters = [])
    {
        foreach ($parameters as $parameter) {
            $this->add($parameter);
        }
    }

    /**
     * Add new parameter to the collection
     * @param ParameterInterface $parameter
     */
    public function add(ParameterInterface $parameter): ParameterInterface
    {
        $this->all[] = $parameter;
        return $this->parameters[$parameter->getName()] = $parameter;
    }

    /**
     * Return all array of route parameters
     * @return ParameterInterface[] the collection of parameters
     */
    public function all(): array
    {
        return $this->all;
    }

    /**
     * Check whether the collection contains the parameter for the
     * given name
     * @param  string  $name the name of the parameter
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * Get the route parameter for the given name from the collection
     * @param  string  $name the name of the parameter
     * @return ParameterInterface
     */
    public function get(string $name): ?ParameterInterface
    {
        return array_key_exists($name, $this->parameters)
                ? $this->parameters[$name]
                : null;
    }

    /**
     * Delete from collection the route parameter for the given name
     * @param  string  $name the name of the parameter
     * @return ParameterCollection
     */
    public function delete(string $name): self
    {
        unset($this->parameters[$name]);
        return $this;
    }
}
