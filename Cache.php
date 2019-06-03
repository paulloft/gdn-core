<?php

namespace Garden;

use function extension_loaded;

abstract class Cache implements Interfaces\Cache {
    const DEFAULT_LIFETIME = 3600;

    public static $clear = false;

    private static $lazyData = [];
    private static $instances = [];
    private static $clearFile = GDN_CACHE . '/.clear_cache';

    /**
     * Get singletone cache class
     *
     * @param string $driver driver name
     * @param array $options initial driver options
     * @return self
     */
    public static function instance($driver = null, array $options = null): self
    {
        self::flush();

        if ($driver === null) {
            $driver = Config::get('cache.driver', 'dirty');
        }

        if (!isset(self::$instances[$driver])) {
            $driverClass = 'Garden\Cache\\' . ucfirst($driver);

            $options = $options ?: Config::get("cache.$driver", []);
            self::$instances[$driver] = new $driverClass($options);
        }

        if (self::$clear) {
            self::$instances[$driver]->deleteAll();
        }

        return self::$instances[$driver];
    }

    /**
     * Request to clear all cache
     */
    public static function clear()
    {
        self::$clear = true;
        self::reset_opcache();
        touch(self::$clearFile);
    }

    /**
     * Get lazy cache
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function lazyGet(string $name, $default = null)
    {
        return self::$lazyData[$name] ?? $default;
    }

    /**
     * Set lazy cache data
     *
     * @param string $name
     * @param $data
     */
    public static function lazySet(string $name, $data)
    {
        self::$lazyData[$name] = $data;
    }

    protected static function flush()
    {
        if (isset($_GET['nocache']) || file_exists(self::$clearFile)) {
            if (!self::$clear && is_file(self::$clearFile)) {
                @unlink(self::$clearFile);
            }

            self::$clear = true;
            self::reset_opcache();
        }
    }

    protected static function reset_opcache()
    {
        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }
}