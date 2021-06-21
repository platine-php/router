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
 *  @file RouteCollection.php
 *
 *  The RouteCollection class is used to manage the collection of routes
 *
 *  @package    Platine\Route
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Route;

use InvalidArgumentException;
use Platine\Route\Exception\RouteAlreadyExistsException;
use Platine\Route\Exception\RouteNotFoundException;

class RouteCollection implements RouteCollectionInterface
{

    /**
     * The list of routes
     * @var Route[]
     */
    protected array $routes = [];

    /**
     * The list of named routes
     * @var array<string, Route>
     */
    protected array $namedRoutes = [];

    /**
     * Create new instance
     * @param Route[] $routes
     */
    public function __construct(array $routes = [])
    {
        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                throw new InvalidArgumentException(sprintf(
                    'Route must be an instance of [%s]',
                    Route::class
                ));
            }
            $this->add($route);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(Route $route): self
    {
        $this->routes[] = $route;

        if ($route->getName() !== '') {
            $name = $route->getName();

            if ($this->has($name)) {
                throw new RouteAlreadyExistsException(
                    sprintf('Route [%s] already added', $name)
                );
            }
            $this->namedRoutes[$name] = $route;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): Route
    {
        if (!$this->has($name)) {
            throw new RouteNotFoundException(
                sprintf('Route [%s] not found', $name)
            );
        }

        return $this->namedRoutes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name): Route
    {
        if (!$this->has($name)) {
            throw new RouteNotFoundException(
                sprintf('Route [%s] not found', $name)
            );
        }
        $removed = $this->namedRoutes[$name];
        unset($this->namedRoutes[$name]);

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->routes = [];
        $this->namedRoutes = [];
    }
}
