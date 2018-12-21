<?php

namespace Garden;

abstract class Cache implements Interfaces\Cache {
    const DEFAULT_LIFETIME = 3600;

    public static $instances = [];
    public static $clear = false;

    private static $clearFile = GDN_CACHE . '/.clear_cache';

    /**
     * get singletone cache class
     * @param string $driver driver name
     * @param array $options initial driver options
     * @return self
     */
    public static function instance($driver = null, array $options = null): self
    {
        self::flush();

        if ($driver === null) {
            $driver = c('cache.driver', 'dirty');
        }

        if (!isset(self::$instances[$driver])) {
            $driverClass = 'Garden\Cache\\' . ucfirst($driver);

            $options = $options ?: c("cache.$driver", []);
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
        if (\extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }
}