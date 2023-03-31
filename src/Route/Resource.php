<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Route;

use Garden\Addons;
use Garden\Event;
use Garden\Exception;
use Garden\Request;
use Garden\Response;
use Garden\Route;
use ReflectionException;
use ReflectionMethod;
use function in_array;
use function is_callable;

/**
 * Maps paths to controllers that act as RESTful resources.
 *
 * The following are examples of urls that will map using this resource route.
 * - METHOD /controller/:id -> method
 * - METHOD /controller/:id/action -> methodAction
 * - GET /controller -> index
 * - METHOD /controller/action -> methodAction
 */
class Resource extends Route
{
    protected $controllerPattern = '%sController';

    /**
     * @var array An array of controller method names that can't be dispatched to by name.
     */
    public static $specialActions = ['index', 'initialize', 'post'];
    public static $defaultActons = [
        Request::METHOD_GET => 'index',
        Request::METHOD_POST => 'index',
        Request::METHOD_OPTIONS => 'options_index',
        Request::METHOD_DELETE => 'delete_index',
        Request::METHOD_PUT => 'put_index',
    ];

    /**
     * @var string controller name for dispatching
     */
    protected $controller;

    /**
     * @var string action name for dispatching
     */
    protected $action;


    /**
     * Initialize an instance of the {@link ResourceRoute} class.
     *
     * @param string $root The url root of that this route will map to.
     * @param string $controllerPattern A pattern sui table for {@link sprintf} that will map
     * a path to a controller object name.
     */
    public function __construct($root = '', $controllerPattern = null)
    {
        $this->setPattern($root);

        if ($controllerPattern !== null) {
            $this->controllerPattern = $controllerPattern;
        }
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
            [$this->arguments, $sysArgs, $printArgs] = $this->getArgs($request);
            $this->controller = $this->getClassName($printArgs);
        } catch (Exception\Pass $ex) {
            return false;
        }

        $method = $request->getMethod();
        $action = $sysArgs['action'] ?? false;

        // Special actions should not be considered.
        if (in_array($action, self::$specialActions, true)) {
            return false;
        }

        if ($action) {
            $this->action = $this->getActionName($this->controller, $action, $method);
        } else {
            $this->action = $this->getActionName($this->controller, self::$defaultActons[$method]);
        }

        if ($this->action === null) {
            return false;
        }

        return $this->isValid();
    }

    /**
     * @return bool
     * @throws
     */
    protected function isValid(): bool
    {
        if ($this->action === null) {
            return false;
        }

        try {
            $reflectionMethod = new ReflectionMethod($this->controller, $this->action);
        } catch (ReflectionException $exception) {
            return false;
        }

        return $reflectionMethod->isPublic();
    }

    /**
     * Dispatch the route.
     *
     * @param Request $request The current request we are dispatching against.
     * @return Response
     * @throws \Exception
     */
    public function dispatch(Request $request): Response
    {
        $controller = new $this->controller();
        $request->setEnvKey('action', $this->action);
        $request->setEnvKey('controller', $this->controller);

        $nameSpacing = explode('\\', ltrim($this->controller, '\\'));

        [$type, $addon] = $nameSpacing;

        if ($type === 'Addons') {
            $request->setEnvKey('addon', $addon);
            $request->setEnvKey('controller_name', array_pop($nameSpacing));
        }

        $response = new Response();
        Response::current($response); // set current

        $actionArgs = $this->getActionArguments($controller, $request, $response);

        ob_start();

        if (method_exists($controller, 'initialize')) {
            $initArgs = array_merge(['action' => $this->action], $actionArgs);
            Event::callUserFuncArray([$controller, 'initialize'], $initArgs);
        }

        $result = Event::callUserFuncArray([$controller, $this->action], $actionArgs);
        $body = ob_get_clean();

        $body .= $response->render($result);

        $response->setBody($body);

        return $response;
    }

    /**
     * Gets corrected
     *
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
        $sysArgs = [];
        $printArgs = [];

        foreach ($matchesArgs as $arg => $value) {
            if (is_numeric($arg)) {
                continue;
            }

            if ($arg === 'addon' || $arg === 'controller' || $arg === 'action') {
                $sysArgs[$arg] = $value;
            } else {
                $args[$arg] = $value;
            }

            $printArgs[] = ucfirst($value);
        }

        return [$args, $sysArgs, $printArgs];
    }

    /**
     * @param array $args
     * @return string
     * @throws Exception\Pass
     */
    protected function getClassName(array $args): string
    {
        $basename = vsprintf($this->controllerPattern, $args);

        if (class_exists('\Garden\Addons', false)) {
            [$classname] = Addons::classMap($basename);

            if ($classname) {
                return $classname;
            }
        }

        if (class_exists($basename)) {
            return $basename;
        }

        throw new Exception\Pass();
    }

    /**
     * @param $controller
     * @param Request $request
     * @return array
     * @throws Exception\InvalidArgument
     * @throws ReflectionException
     */
    protected function getActionArguments($controller, Request $request, Response $response): array
    {
        $actionArgs = $this->arguments;

        // Make sure the number of action arguments match the action method.
        $actionMethod = new ReflectionMethod($controller, $this->action);
        $this->action = $actionMethod->getName(); // make correct case.
        $actionParams = $actionMethod->getParameters();

        // Fill in missing default parameters.
        foreach ($actionParams as $i => $param) {
            $paramName = $param->getName();

            if ($this->isMapped($paramName)) {
                // The parameter is mapped to a specific request item.
                array_splice($actionArgs, $i, 0, [$this->mappedData($paramName, $request, $response)]);
            } elseif (!isset($actionArgs[$paramName])) {
                if ($param->isDefaultValueAvailable()) {
                    $actionArgs[$paramName] = $param->getDefaultValue();
                } else {
                    throw new Exception\InvalidArgument("Missing argument $i for {$this->controller}::{$this->action}().");
                }
            } elseif ($this->failsCondition($paramName, $actionArgs[$paramName])) {
                throw new Exception\InvalidArgument("Invalid argument '{$actionArgs[$paramName]}' for {$paramName}.");
            }
        }

        return $actionArgs;
    }

    /**
     * Tests whether a controller action exists.
     *
     * @param string $controller The controller object that the method should be on.
     * @param string $action The name of the action.
     * @param string $method The http method.
     * @return string|null Returns the name of the action method or an empty string if it doesn't exist.
     */
    protected function getActionName(string $controller, string $action, string $method = ''): ?string
    {
        // Short circuit on a badly named action.
        if (!preg_match('`[_a-zA-Z][_a-zA-Z0-9]{0,30}`i', $action)) {
            return null;
        }

        if ($method) {
            $method = strtolower($method);
            $calledAction = $method . '_' . $action;
            if ($method !== $action && Event::methodExists($controller, $calledAction)) {
                return $calledAction;
            }
        }

        if (Event::methodExists($controller, $action)) {
            return $action;
        }

        return null;
    }

    /**
     * Tests whether an argument fails against a condition.
     *
     * @param string $name The name of the parameter.
     * @param string $value The value of the argument.
     * @return bool|null Returns one of the following:
     * - true: The condition fails.
     * - false: The condition passes.
     */
    protected function failsCondition($name, $value)
    {
        $name = strtolower($name);

        if (isset($this->conditions[$name])) {
            $regex = $this->conditions[$name];
            return !preg_match("`^$regex$`", $value);
        }

        if (isset(self::$globalConditions[$name])) {
            $regex = self::$globalConditions[$name];
            return !preg_match("`^$regex$`", $value);
        }

        return false;
    }
}
