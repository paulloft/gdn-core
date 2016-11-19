<?php
namespace Garden;

class Factory {
    /**
     * @var array The singleton instances of the plugin subclasses.
     */
    private static $instances;
    private static $aliases;
    private static $args;

    /**
     * Return class instance by name or alias
     *
     * @param $className
     * @return \$className
     */
    public static function get($className)
    {
        $alias = trim(strtolower($className), "\\");
        if(self::$args) {
            $args = self::$args;
            self::$args = null;
        } else {
            $args = func_get_args();
            array_shift($args);
        }

        if(isset(self::$aliases[$alias])) {
            $className = self::$aliases[$alias];
        }

        $className = trim($className, "\\");

        // $hash = self::hash($className, $args);

        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = self::_instantiateObject($className, $args);
        }

        return self::$instances[$className];
    }

    /**
     * Return class instance by name or alias with array params
     *
     * @param $className
     * @return \$className
     */
    public static function getArray($className, $args)
    {
        self::$args = $args;
        return self::get($className);
    }

    /**
     * Set alias for class
     *
     * @param string $alias alias for class
     * @param string $class class name
     * @param bool $init initialise and return instance class
     * @return \$class|bool
     */
    public static function install($alias, $class, $init = false)
    {
        $alias = strtolower($alias);

        if(isset(self::$aliases[$alias])) return false;
        self::$aliases[$alias] = $class;

        return $init ? self::get($alias) : true;
    }

    /**
     * Check if class exists in factory
     *
     * @param string $className
     * @return bool
     */
    public static function exists($className)
    {
        $alias = trim(strtolower($className), "\\");
        if(self::$args) {
            $args = self::$args;
            self::$args = null;
        } else {
            $args = func_get_args();
            array_shift($args);
        }

        if(isset(self::$aliases[$alias])) {
            $className = self::$aliases[$alias];
        }

        $className = trim($className, "\\");

        // $hash = self::hash($className, $args);

        return isset(self::$instances[$className]);
    }

    /**
     * Instantiate a new object.
     *
     * @param string $className The name of the class to instantiate.
     * @param array $args The arguments to pass to the constructor.
     * Note: This function currently only supports a maximum of 8 arguments.
     */
    protected static function _instantiateObject($className, array $args = array())
    {
        $result = NULL;

        // Instantiate the object with the correct arguments.
        // This odd looking case statement is purely for speed optimization.
        if(class_exists($className)) switch(count($args)) {
            case 0:
                $result = new $className; break;
            case 1:
                $result = new $className($args[0]); break;
            case 2:
                $result = new $className($args[0], $args[1]); break;
            case 3:
                $result = new $className($args[0], $args[1], $args[2]); break;
            case 4:
                $result = new $className($args[0], $args[1], $args[2], $args[3]); break;
            case 5:
                $result = new $className($args[0], $args[1], $args[2], $args[3], $args[4]); break;
            case 6:
                $result = new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]); break;
            case 7:
                $result = new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]); break;
            case 8:
                $result = new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]); break;
            default:
                throw new Exception();
        }

        return $result;
    }

    protected static function hash($alias, array $args = array())
    {
        $alias = trim(strtolower($alias), "\\");
        return empty($args) ? $alias : md5($alias.implode($args));
    }
}