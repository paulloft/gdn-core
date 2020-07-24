<?php

namespace Garden\Traits;

trait Instance
{
    /**
     * @var array
     */
    private static $instances;

    /**
     * Returns the application singleton or null if the singleton has not been created yet.
     * @param mixed ...$args
     * @return $this
     */
    public static function instance(...$args): self
    {
        $className = static::class;

        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new $className(...$args);
        }

        return self::$instances[$className];
    }
}