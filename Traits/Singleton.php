<?php
namespace Garden\Traits;

trait Singleton
{
    private static $instance;
    /**
     * Returns the application singleton or null if the singleton has not been created yet.
     * @return $this
     */
    public static function instance(...$args) {
        if (!self::$instance) {
            self::$instance = new self(...$args);
        }

        return self::$instance;
    }

    private function __clone() {}
    private function __construct() {}
}