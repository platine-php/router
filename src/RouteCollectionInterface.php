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
 *  @file RouteCollectionInterface.php
 *
 *  The RouteCollectionInterface interface
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

use Platine\Route\Exception\RouteAlreadyExistsException;
use Platine\Route\Exception\RouteNotFoundException;

interface RouteCollectionInterface
{

    /**
     * Add new route
     *
     * @param Route $route
     * @throws RouteAlreadyExistsException
     */
    public function add(Route $route): self;

    /**
     * Get the route for the given name
     * @param  string $name the route name
     * @return Route
     *
     * @throws RouteNotFoundException if the route does not exist.
     */
    public function get(string $name): Route;

    /**
     * Return all routes
     * @return Route[]
     */
    public function all(): array;

    /**
     * Whether a route with the specified name exists.
     * @param  string  $name the route name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Remove the route for the given name
     * @param  string $name the route name
     * @return Route route that is removed.
     *
     * @throws RouteNotFoundException if the route does not exist.
     */
    public function remove(string $name): Route;

    /**
     * Remove all routes
     * @return void
     */
    public function clear(): void;
}
