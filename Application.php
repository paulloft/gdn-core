<?php

namespace Garden;

class Application {
    /// Properties ///
    protected static $instances;

    /**
     * @var Request The current request.
     */
    public $request;

    /**
     *
     * @var Response The current response.
     */
    public $response;

    /**
     * @var array An array of route objects.
     */
    protected $routes;

    /// Methods ///

    public function __construct($name = 'default')
    {
        $this->routes = [];

        self::$instances[$name] = $this;
    }

    /**
     * @param string $name
     * @return self
     */
    public static function instance($name = 'default'): self
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new Application($name);
        }

        return self::$instances[$name];
    }

    /**
     * Get all of the matched routes for a request.
     *
     * @param Request $request The {@link Request} to match against.
     * @return array An array of arrays corresponding to matching routes and their args.
     */
    public function matchRoutes(Request $request): array
    {
        $result = [];

        foreach ($this->routes as $route) {
            $matches = $route->matches($request);
            if ($matches) {
                $result[] = [$route, $matches];
            }
        }

        return $result;
    }

    /**
     * Get matched route for a request.
     *
     * @return Route
     * @throws Exception\NotFound() Throws a 404 when the path doesn't map to a controller action.
     */
    public function findRoute(): Route
    {
        /**
         * @var $route Route
         */
        foreach ($this->routes as $route) {
            if ($route->match($this->request)) {
                return $route;
            }
        }

        throw new Exception\NotFound();
    }

    /**
     * Add a new route.
     *
     * @param string|Route $pathOrRoute The path to the route or the {@link Route} object itself.
     * @param callable|string|null $callback Either a callback to map the route to or a string representing
     * a format for {@link sprintf()}.
     * @return Route Returns the route that was added.
     * @throws \InvalidArgumentException Throws an exceptio if {@link $path} isn't a string or {@link Route}.
     */
    public function route($pathOrRoute, $callback = null): Route
    {
        if ($pathOrRoute instanceof Route) {
            $route = $pathOrRoute;
        } elseif (\is_string($pathOrRoute) && $callback !== null) {
            $route = Route::create($pathOrRoute, $callback);
        } else {
            throw new \InvalidArgumentException("Argument #1 must be either a Garden\\Route or a string.", 500);
        }
        $this->routes[] = $route;
        return $route;
    }

    /**
     * Route to a GET request.
     *
     * @param string $pattern The url pattern to match.
     * @param callable $callback The callback to execute on the route.
     * @throws \InvalidArgumentException
     * @return Route Returns the new route.
     */
    public function get($pattern, callable $callback): Route
    {
        return $this->route($pattern, $callback)->methods(['GET']);
    }

    /**
     * Route to a POST request.
     *
     * @param string $pattern The url pattern to match.
     * @param callable $callback The callback to execute on the route.
     * @throws \InvalidArgumentException
     * @return Route Returns the new route.
     */
    public function post($pattern, callable $callback): Route
    {
        return $this->route($pattern, $callback)->methods(['POST']);
    }

    /**
     * Route to a PUT request.
     *
     * @param string $pattern The url pattern to match.
     * @param callable $callback The callback to execute on the route.
     * @throws \InvalidArgumentException
     * @return Route Returns the new route.
     */
    public function put($pattern, callable $callback): Route
    {
        return $this->route($pattern, $callback)->methods(['PUT']);
    }

    /**
     * Route to a PATCH request.
     *
     * @param string $pattern The url pattern to match.
     * @param callable $callback The callback to execute on the route.
     * @throws \InvalidArgumentException
     * @return Route Returns the new route.
     */
    public function patch($pattern, callable $callback): Route
    {
        return $this->route($pattern, $callback)->methods(['PATCH']);
    }

    /**
     * Route to a DELETE request.
     *
     * @param string $pattern The url pattern to match.
     * @param callable $callback The callback to execute on the route.
     * @throws \InvalidArgumentException
     * @return Route Returns the new route.
     */
    public function delete($pattern, callable $callback): Route
    {
        return $this->route($pattern, $callback)->methods(['DELETE']);
    }

    /**
     * Run the application against a {@link Request}.
     *
     * @param Request $request A {@link Request} to run the application against or null to run against a request
     * on the current environment.
     * @throws \Exception
     */
    public function run(Request $request = null)
    {
        $this->request = Request::current($request ?: new Request());

        Event::fire('dispatch_before');

        try {
            $route = $this->findRoute();
            $args = $route->getMathedArguments();
            Event::fire('dispatch', $request, $args);
            $response = $route->dispatch($this->request);
        } catch (\Exception $ex) {
            $response = Response::create($ex);
            ob_start();
            $handled = Event::fire('exception', $ex);
            ob_get_clean();
            $response->setBody($handled);

            if (!$handled) {
                throw $ex;
            }
        }

        Event::fire('dispatch_after');

        $this->finalize($response);
    }

    /**
     * Finalize the result from a dispatch.
     *
     * @param mixed $result The result of the dispatch.
     * @throws \Exception Throws an exception when finalizing internal content types and the result is an exception.
     */
    protected function finalize(Response $response)
    {
        $response->setMeta(['request' => $this->request], true);
        $response->setContentTypeFromAccept($this->request->getEnvKey('HTTP_ACCEPT'));

        $response->flushHeaders();

        if ($this->request->getMethod() === Request::METHOD_HEAD) {
            return;
        }

        echo $response->body();
    }
}
