<?php

namespace Garden\Traits;

trait Instance
{
    private static $instances;
    /**
     * Returns the application singleton or null if the singleton has not been created yet.
     * @return $this
     */
    public static function instance(...$args) {
        $class_name = get_called_class();
        if (!self::$instances[$class_name]) {
            self::$instances[$class_name] = new $class_name(...$args);
        }

        return self::$instances[$class_name];
    }
}