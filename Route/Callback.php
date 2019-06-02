<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Route;

use Garden\Event;
use Garden\Exception;
use Garden\Request;
use Garden\Response;
use Garden\Route;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Closure;
use function count;
use function get_class;
use function is_array;
use function is_string;

/**
 * A route that maps urls to callbacks.
 */
class Callback extends Route {

    /**
     * @var callable The callback to call on a matching pattern.
     */
    protected $callback;


    /**
     * Initialize an instance of the {@link CallbackRoute} class.
     *
     * @param string $pattern The pattern to match to.
     * @param callable $callback The callback to call when the url matches.
     */
    public function __construct($pattern, callable $callback)
    {
        $this->setPattern($pattern);
        $this->callback = $callback;
    }

    /**
     * Try matching a route to a request.
     *
     * @param Request $request The request to match the route with.
     * @return bool return true if the route matched
     */
    public function match(Request $request): bool
    {
        if (!$this->matchesMethods($request)) {
            return false;
        }

        try {
            $this->arguments = $this->getArgs($request);
        } catch (Exception\Pass $ex) {
            return false;
        }

        return true;
    }

    /**
     * Dispatch the matched route and call its callback.
     *
     * @param Request $request The request to dispatch.
     * @return Response
     * @throws \Exception
     */
    public function dispatch(Request $request): Response
    {
        $response = new Response;
        Response::current($response);

        ob_start();

        $callArgs = $this->getCallArguments($this->callback);
        if (is_array($this->callback) && method_exists($this->callback[0], 'initialize')) {
            Event::callUserFuncArray([$this->callback[0], 'initialize'], $callArgs);
        }

        $body = ob_get_clean();

        $result = Event::callUserFuncArray($this->callback, $callArgs);
        $body .= $response->render($result);

        $response->setBody($body);

        return $response;
    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception\Pass
     */
    protected function getArgs(Request $request): array
    {
        if ($this->getMatchFullPath()) {
            $path = $request->getFullPath();
        } else {
            $path = $request->getPathExt();
        }

        $regex = $this->getPatternRegex();

        if (!preg_match($regex, $path, $matchesArgs)) {
            throw new Exception\Pass();
        }

        $args = [];

        foreach ($matchesArgs as $arg => $value) {
            if (is_numeric($arg)) {
                continue;
            }

            $args[$arg] = $value;
        }

        return $args;
    }

    /**
     * @param $callback
     * @return array
     * @throws Exception\InvalidArgument
     * @throws ReflectionException
     */
    protected function getCallArguments($callback): array
    {
        if ($callback instanceof Closure || is_string($callback)) {
            $method = new ReflectionFunction($callback);
            $methodName = $method;
        } else {
            $method = new ReflectionMethod($callback[0], $callback[1]);
            if (is_string($callback[0])) {
                $methodName = $callback[0] . '::' . $method->getName();
            } else {
                $methodName = get_class($callback[0]) . '->' . $method->getName();
            }
        }

        $params = $method->getParameters();
        $missArgs = $callArgs = [];

        // Set all of the parameters.
        foreach ($params as $index => $param) {
            $paramName = $param->getName();
            $paramNamel = strtolower($paramName);

            if (isset($args[$paramNamel])) {
                $paramValue = $this->arguments[$paramNamel];
            } elseif (isset($args[$index])) {
                $paramValue = $this->arguments[$index];
            } elseif ($param->isDefaultValueAvailable()) {
                $paramValue = $param->getDefaultValue();
            } else {
                $paramValue = null;
                $missArgs[] = '$' . $paramName;
            }

            $callArgs[$paramName] = $paramValue;
        }

        if (count($missArgs) > 0) {
            throw new Exception\InvalidArgument("$methodName() expects the following parameters: " . implode(', ', $missArgs) . '.', $missArgs);
        }

        for ($index = count($callArgs); array_key_exists($index, $this->arguments); $index++) {
            $callArgs[$index] = $this->arguments[$index];
        }

        return $callArgs;
    }

    /**
     * Get the callback for the route.
     *
     * @return callable Returns the current callback.
     */
    public function getCallback()
    {
        return $this->callback;
    }
}

