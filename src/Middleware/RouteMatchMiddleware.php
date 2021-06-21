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
 *  @file RouteMatchMiddleware.php
 *
 *  The RouteMatchMiddleware class is used to match the request
 *
 *  @package    Platine\Route\Middleware
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Route\Middleware;

use Platine\Http\Handler\Middleware\MiddlewareInterface;
use Platine\Http\Handler\RequestHandlerInterface;
use Platine\Http\Response;
use Platine\Http\ResponseInterface;
use Platine\Http\ServerRequestInterface;
use Platine\Route\Route;
use Platine\Route\Router;

class RouteMatchMiddleware implements MiddlewareInterface
{

    /**
     * The Router instance
     * @var Router
     */
    protected Router $router;

    /**
     * The list of allowed methods
     * @var array<string>
     */
    protected array $allowedMethods = [];

    /**
     * Create new instance
     * @param Router $router
     * @param array<string>  $allowedMethods the default allowed methods
     */
    public function __construct(Router $router, array $allowedMethods = ['HEAD'])
    {
        $this->router = $router;

        foreach ($allowedMethods as $method) {
            if (is_string($method)) {
                $this->allowedMethods[] = strtoupper($method);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (!$route = $this->router->match($request, false)) {
            return $handler->handle($request);
        }

        if (
                !$this->isAllowedMethod($request->getMethod())
                && !$route->isAllowedMethod($request->getMethod())
        ) {
            return $this->emptyResponseWithAllowedMethods($route->getMethods());
        }

        foreach ($route->getParameters()->all() as $parameter) {
            $request = $request->withAttribute(
                $parameter->getName(),
                $parameter->getValue()
            );
        }

        $request = $request->withAttribute(Route::class, $route);
        return $handler->handle($request);
    }

    /**
     * Return an empty response with allowed methods
     * @param  array<string>  $methods the list of allowed methods
     * @return ResponseInterface
     */
    protected function emptyResponseWithAllowedMethods(array $methods): ResponseInterface
    {
        foreach ($this->allowedMethods as $method) {
            if (is_string($method)) {
                $methods[] = strtoupper($method);
            }
        }

        $methods = implode(', ', array_unique(array_filter($methods)));

        return (new Response(405))->withHeader('Allow', $methods);
    }

    /**
     * Check whether the given method is allowed
     * @param  string  $method
     * @return bool
     */
    protected function isAllowedMethod(string $method): bool
    {
        return (empty($this->allowedMethods)
                || in_array(strtoupper($method), $this->allowedMethods, true));
    }
}
