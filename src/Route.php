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
 *  @file Route.php
 *
 *  The Route class used to describe each route data
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

use InvalidArgumentException;
use Platine\Http\ServerRequestInterface;
use Platine\Http\Uri;
use Platine\Http\UriInterface;
use Platine\Route\Exception\InvalidRouteParameterException;

class Route
{
    /**
     * Search through the given route looking for dynamic portions.
     *
     * Using ~ as the regex delimiter.
     *
     * We start by looking for a literal '{' character followed by any amount
     * of whitespace. The next portion inside the parentheses looks for a parameter name
     * containing alphanumeric characters or underscore.
     *
     * After this we look for the ':\d+' and ':[0-9]+'
     * style portion ending with a closing '}' character.
     *
     * Finally we look for an optional '?' which is used to signify
     * an optional route parameter.
     */
    private const PARAMETERS_PLACEHOLDER = '~\{\s*([a-zA-Z_][a-zA-Z0-9_-]*)\s*(?::\s*([^{}]*(?:\{(?-1)\}[^{}]*)*))?\}~';

    /**
     * The default parameter character restriction
     * (One or more characters that is not a '/').
     */
    private const DEFAULT_PARAMETERS_REGEX = '[^\/]+';

    /**
     * The route name
     * @var string
     */
    protected string $name;

    /**
     * The path pattern with parameters
     * @var string
     */
    protected string $pattern;

    /**
     * The route handler
     * action, controller, callable, closure, etc.
     * @var mixed
     */
    protected $handler;

    /**
     * The route allowed request methods
     * @var array<string>
     */
    protected array $methods = [];

    /**
     * The instance ParameterCollection.
     * @var ParameterCollection
     */
    protected ParameterCollection $parameters;

    /**
     * The list of routes parameters shortcuts
     * @var array<string, string>
     */
    protected array $parameterShortcuts = [
        ':i}' => ':[0-9]+}',
        ':a}' => ':[0-9A-Za-z]+}',
        ':al}' => ':[a-zA-Z0-9+_\-\.]+}',
        ':any}' => ':.*}',
    ];

    /**
     * The route attributes in order to add some
     * additional information
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Create the new instance
     * @param string $pattern       path pattern with parameters.
     * @param mixed $handler       action, controller, callable, closure, etc.
     * @param string|null $name       the route name
     * @param array<string>  $methods the route allowed methods
     * @param array<string, mixed>  $attributes the route attributes
     */
    public function __construct(
        string $pattern,
        $handler,
        ?string $name = null,
        array $methods = [],
        array $attributes = []
    ) {
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->parameters = new ParameterCollection();
        $this->name = $name ?? '';
        $this->attributes = $attributes;

        foreach ($methods as $method) {
            if (!is_string($method)) {
                throw new InvalidRouteParameterException(sprintf(
                    'Invalid request method [%s], must be a string',
                    $method
                ));
            }

            $this->methods[] = strtoupper($method);
        }
    }

    /**
     * Whether the route has the given attribute
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Return the value of the given attribute
     * @param string $name
     * @return mixed|null
     */
    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Set attribute value
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $name, $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Remove the given attribute
     * @param string $name
     * @return $this
     */
    public function removeAttribute(string $name): self
    {
        unset($this->attributes[$name]);

        return $this;
    }

    /**
     * Return the route name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the route name.
     *
     * @param string $name the new route name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Return the route pattern
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Return the route handler
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Return the route request methods
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Return the ParameterCollection for this route.
     *
     * @return ParameterCollection
     */
    public function getParameters(): ParameterCollection
    {
        return $this->parameters;
    }

    /**
     * Checks whether the request method is allowed for the current route.
     * @param  string  $method
     * @return bool
     */
    public function isAllowedMethod(string $method): bool
    {
        return (empty($this->methods)
                || in_array(
                    strtoupper($method),
                    $this->methods,
                    true
                ));
    }

    /**
     * Checks whether the request URI matches the current route.
     *
     * If there is a match and the route has matched parameters, they will
     * be saved and available via the `Route::getParameters()` method.
     *
     * @param  ServerRequestInterface $request
     * @param  string $basePath
     * @return bool
     */
    public function match(ServerRequestInterface $request, string $basePath = '/'): bool
    {
        $routePattern = $this->pattern;
        $pattern = strtr($routePattern, $this->parameterShortcuts);

        preg_match_all(self::PARAMETERS_PLACEHOLDER, $pattern, $matches);

        foreach ($matches[0] as $key => $value) {
            $parameterName = ($matches[1][$key] !== '')
                    ? $matches[1][$key]
                    : $matches[2][$key];

            $parameterPattern = sprintf('(%s)', self::DEFAULT_PARAMETERS_REGEX);
            if ($matches[1][$key] !== '' && $matches[2][$key] !== '') {
                $parameterPattern = sprintf('(%s)', $matches[2][$key]);
            }
            $this->parameters->add(new Parameter($parameterName, null));
            $pattern = str_replace($value, $parameterPattern, $pattern);
        }

        $requestPath = $request->getUri()->getPath();
        if ($basePath !== '/') {
            $basePathLength = strlen($basePath);
            if (substr($requestPath, 0, $basePathLength) === $basePath) {
                $requestPath = substr($requestPath, $basePathLength);
            }
        }

        if (
            preg_match(
                '~^' . $pattern . '$~i',
                rawurldecode($requestPath),
                $matches
            )
        ) {
            array_shift($matches);

            foreach ($this->parameters->all() as $parameter) {
                $parameter->setValue(array_shift($matches));
            }

            return true;
        }

        return false;
    }

    /**
     * Return the URI for this route
     * @param  array<string, mixed>  $parameters the route parameters
     * @param string $basePath the base path
     * @return UriInterface
     */
    public function getUri(array $parameters = [], $basePath = '/'): UriInterface
    {
        $pattern = $this->pattern;
        if ($basePath !== '/') {
            $pattern = rtrim($basePath, '/') . $pattern;
        }
        $uri = strtr($pattern, $this->parameterShortcuts);

        $matches = [];
        preg_match_all(self::PARAMETERS_PLACEHOLDER, $uri, $matches);

        foreach ($matches[0] as $key => $value) {
            $parameterName = ($matches[1][$key] !== '')
                    ? $matches[1][$key]
                    : $matches[2][$key];

            $parameterPattern = sprintf('(%s)', self::DEFAULT_PARAMETERS_REGEX);
            if ($matches[1][$key] !== '' && $matches[2][$key] !== '') {
                $parameterPattern = sprintf('(%s)', $matches[2][$key]);
            }

            if (
                    isset($parameters[$parameterName])
                    && preg_match(
                        '/^' . $parameterPattern . '$/',
                        (string) $parameters[$parameterName]
                    )
            ) {
                $uri = str_replace($value, (string) $parameters[$parameterName], $uri);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Parameter [%s] is not passed',
                    $parameterName
                ));
            }
        }

        return new Uri($uri);
    }

    /**
     * Generates the URL path from the route parameters.
     * @param  array<string, mixed>  $parameters parameter-value set.
     * @param string $basePath the base path
     * @return string URL path generated.
     */
    public function path(array $parameters = [], $basePath = '/'): string
    {
        return $this->getUri($parameters, $basePath)->getPath();
    }
}
