<?php
namespace Garden;

abstract class Cache implements Interfaces\Cache
{
    const DEFAULT_LIFETIME = 3600;

    /**
     * @var   string     default driver to use
     */
    public static $default = 'dirty';
    public static $instances = [];
    public static $clear = false;

    private static $clearFile = GDN_CACHE . '/.clear_cache';

    /**
     * get singletone cache class
     * @param string $driver driver name
     * @return self
     * @throws Exception\Custom
     */
    public static function instance($driver = null, $config = false)
    {
        $options = c('cache');

        self::flush();

        if (!$driver) {
            $driver = val('driver', $options, self::$default);
        }

        if (!isset(self::$instances[$driver])) {
            $driverClass = 'Garden\Cache\\' . ucfirst($driver);

            if (!class_exists($driverClass)) {
                throw new Exception\Custom('Cache driver "%s" not found', [$driver]);
            }

            $config = $config ?: val($driver, $options);
            self::$instances[$driver] = new $driverClass($config);
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
        self::_reset_opcache();
        touch(self::$clearFile);
    }

    protected static function flush()
    {
        if (isset($_GET['nocache']) || file_exists(self::$clearFile)) {
            if (!self::$clear && is_file(self::$clearFile)) {
                @unlink(self::$clearFile);
            }

            self::$clear = true;
            self::_reset_opcache();
        }
    }

    protected static function _reset_opcache()
    {
        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }
}