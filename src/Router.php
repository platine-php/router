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
 *  @file Router.php
 *
 *  The Router class is used to route the request to the handler
 *  for response generation
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

use Platine\Http\ServerRequestInterface;
use Platine\Http\UriInterface;
use Platine\Route\Exception\RouteNotFoundException;

class Router
{
    /**
     * The current route group prefix
     * @var string
     */
    protected string $groupPrefix = '';

    /**
     * The instance of RouteCollectionInterface
     * @var  RouteCollectionInterface
     */
    protected RouteCollectionInterface $routes;

    /**
     * The base path to use
     * @var string
     */
    protected string $basePath = '/';

    /**
     * Create new Router instance
     * @param RouteCollectionInterface|null $routes
     */
    public function __construct(?RouteCollectionInterface $routes = null)
    {
        $this->routes = $routes ? $routes : new RouteCollection();
    }

    /**
     * Set base path
     * @param string $basePath
     * @return $this
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }


    /**
     * Return the instance of RouteCollectionInterface with all routes set.
     *
     * @return RouteCollectionInterface
     */
    public function routes(): RouteCollectionInterface
    {
        return $this->routes;
    }

    /**
     * Create a route group with a common prefix.
     *
     * The callback can take a Router instance as a parameter.
     * All routes created in the passed callback will have the given group prefix prepended.
     *
     * @param  string   $prefix   common path prefix for the route group.
     * @param  callable $callback callback that will add routes with a common path prefix.
     * @return void
     */
    public function group(string $prefix, callable $callback): void
    {
        $currentGroupPrefix = $this->groupPrefix;
        $this->groupPrefix = $currentGroupPrefix . $prefix;
        $callback($this);
        $this->groupPrefix = $currentGroupPrefix;
    }

    /**
     * Add new route and return it
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @param string[]  $methods allowed request methods of the route.
     * @param string $name  the  route name.
     * @param array<string, mixed> $attributes the route attributes.
     *
     * @return Route
     */
    public function add(
        string $pattern,
        $handler,
        array $methods,
        string $name = '',
        array $attributes = []
    ): Route {
        $pattern = $this->groupPrefix . $pattern;
        $route = new Route($pattern, $handler, $name, $methods, $attributes);
        $this->routes->add($route);

        return $route;
    }


    /**
     * Add a generic route for any request methods and returns it.
     *
     * @param  string $pattern path pattern with parameters.
     * @param  mixed $handler action, controller, callable, closure, etc.
     * @param  string $name    the  route name.
     * @param array<string, mixed> $attributes the route attributes.
     * @return Route the new route added
     */
    public function any(string $pattern, $handler, string $name = '', array $attributes = []): Route
    {
        return $this->add($pattern, $handler, [], $name, $attributes);
    }

    /**
     * Add a GET route and returns it.
     *
     * @see  Router::add
     * @param mixed $handler action, controller, callable, closure, etc.
     * @param array<string, mixed> $attributes the route attributes.
     */
    public function get(string $pattern, $handler, string $name = '', array $attributes = []): Route
    {
        return $this->add($pattern, $handler, ['GET'], $name, $attributes);
    }

    /**
     * Add a POST route and returns it.
     *
     * @see  Router::add
     * @param mixed $handler action, controller, callable, closure, etc.
     * @param array<string, mixed> $attributes the route attributes.
     */
    public function post(string $pattern, $handler, string $name = '', array $attributes = []): Route
    {
        return $this->add($pattern, $handler, ['POST'], $name, $attributes);
    }

    /**
     * Add a PUT route and returns it.
     *
     * @see  Router::add
     * @param mixed $handler action, controller, callable, closure, etc.
     * @param array<string, mixed> $attributes the route attributes.
     */
    public function put(string $pattern, $handler, string $name = '', array $attributes = []): Route
    {
        return $this->add($pattern, $handler, ['PUT'], $name, $attributes);
    }

    /**
     * Add a PATCH route and returns it.
     *
     * @see  Router::add
     * @param mixed $handler action, controller, callable, closure, etc.
     * @param array<string, mixed> $attributes the route attributes.
     */
    public function patch(string $pattern, $handler, string $name = '', array $attributes = []): Route
    {
        return $this->add($pattern, $handler, ['PATCH'], $name, $attributes);
    }

    /**
     * Add a DELETE route and returns it.
     *
     * @see  Router::add
     * @param mixed $handler action, controller, callable, closure, etc.
     * @param array<string, mixed> $attributes the route attributes.
     */
    public function delete(string $pattern, $handler, string $name = '', array $attributes = []): Route
    {
        return $this->add($pattern, $handler, ['DELETE'], $name, $attributes);
    }

    /**
     * Add a HEAD route and returns it.
     *
     * @see  Router::add
     * @param mixed $handler action, controller, callable, closure, etc.
     * @param array<string, mixed> $attributes the route attributes.
     */
    public function head(string $pattern, $handler, string $name = '', array $attributes = []): Route
    {
        return $this->add($pattern, $handler, ['HEAD'], $name, $attributes);
    }

    /**
     * Add a OPTIONS route and returns it.
     *
     * @see  Router::add
     * @param mixed $handler action, controller, callable, closure, etc.
     * @param array<string, mixed> $attributes the route attributes.
     */
    public function options(string $pattern, $handler, string $name = '', array $attributes = []): Route
    {
        return $this->add($pattern, $handler, ['OPTIONS'], $name, $attributes);
    }

    /**
     * Add a resource route.
     *
     * @param  string $pattern path pattern with parameters.
     * @param  class-string $handler action class.
     * @param  string $name the  route name and permission prefix.
     * @param  bool $permission whether to use permission.
     * @param array<string, mixed> $attributes the route attributes.
     * @return $this
     */
    public function resource(
        string $pattern,
        string $handler,
        string $name = '',
        bool $permission = true,
        array $attributes = []
    ): self {

        $maps = [
            [
                'path' => '',
                'action' => '%s@index',
                'route_name' => '%s_list',
                'method' => 'get',
                'csrf' => false,
                'many' => false,
                'permission' => $permission ? '%s_list' : null,
            ],
            [
                'path' => '/detail/{id}',
                'action' => '%s@detail',
                'route_name' => '%s_detail',
                'method' => 'get',
                'csrf' => false,
                'many' => false,
                'permission' => $permission ? '%s_detail' : null,
            ],
            [
                'path' => '/create',
                'action' => '%s@create',
                'route_name' => '%s_create',
                'method' => 'add',
                'csrf' => false,
                'many' => true,
                'permission' => $permission ? '%s_create' : null,
            ],
            [
                'path' => '/update/{id}',
                'action' => '%s@update',
                'route_name' => '%s_update',
                'method' => 'add',
                'csrf' => false,
                'many' => true,
                'permission' => $permission ? '%s_update' : null,
            ],
            [
                'path' => '/delete/{id}',
                'action' => '%s@delete',
                'route_name' => '%s_delete',
                'method' => 'get',
                'csrf' => true,
                'many' => false,
                'permission' => $permission ? '%s_delete' : null,
            ],
        ];

        if (empty($name)) {
            $name = trim($pattern, '/');
        }

        $this->group($pattern, function (Router $router) use ($handler, $maps, $name, $attributes) {
            foreach ($maps as $map) {
                if ($map['many']) {
                    /** @var Route $route */
                    $route = $router->{$map['method']}(
                        $map['path'],
                        sprintf($map['action'], $handler),
                        ['GET', 'POST'],
                        sprintf($map['route_name'], $name),
                        $attributes
                    );
                } else {
                    /** @var Route $route */
                    $route = $router->{$map['method']}(
                        $map['path'],
                        sprintf($map['action'], $handler),
                        sprintf($map['route_name'], $name),
                        $attributes
                    );
                }

                if ($map['permission']) {
                    $route->setAttribute('permission', sprintf($map['permission'], $name));
                }

                if ($map['csrf']) {
                    $route->setAttribute('csrf', true);
                }
            }
        });

        return $this;
    }


    /**
     * Matches the request against known routes.
     * @param  ServerRequestInterface $request
     * @param  bool $checkAllowedMethods whether to check if the
     * request method matches the allowed route methods.
     * @return Route|null matched route or null if the
     * request does not match any route.
     */
    public function match(
        ServerRequestInterface $request,
        bool $checkAllowedMethods = true
    ): ?Route {
        $notAllowedMethodRoute = null;
        foreach ($this->routes->all() as $route) {
            if (!$route->match($request, $this->basePath)) {
                continue;
            }

            if ($route->isAllowedMethod($request->getMethod())) {
                return $route;
            }

            if ($notAllowedMethodRoute === null) {
                $notAllowedMethodRoute = $route;
            }
        }

        return $checkAllowedMethods ? null : $notAllowedMethodRoute;
    }

    /**
     * Return the Uri for this route
     * @param  string  $name the route name
     * @param  array<string, mixed>  $parameters the route parameters
     * @return UriInterface
     *
     * @throws RouteNotFoundException if the route does not exist.
     */
    public function getUri(string $name, array $parameters = []): UriInterface
    {
        if ($this->routes->has($name)) {
            return $this->routes->get($name)->getUri($parameters, $this->basePath);
        }

        throw new RouteNotFoundException(sprintf('Route [%s] not found', $name));
    }

    /**
     * Generates the URL path from the named route and parameters.
     * @param  string $name
     * @param  array<string, mixed>  $parameters
     * @return string
     *
     * @throws RouteNotFoundException if the route does not exist.
     */
    public function path(string $name, array $parameters = []): string
    {
        return $this->getUri($name, $parameters)->getPath();
    }
}
