<?php
namespace Garden;
use \Garden\Exception;
/**
* 
*/
abstract class Cache
{
    const DEFAULT_LIFETIME = 3600;

    /**
     * @var   string     default driver to use
     */
    public static $default = 'dirty';
    public static $instances = array();
    public static $clear = false;

    private static $clearFile = GDN_CACHE.'/.clear_cache';

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

        if(!$driver) {
            $driver = val('driver', $options, self::$default);
        }

        if (isset(self::$instances[$driver])) {
            return self::$instances[$driver];
        }

        $driverClass = 'Garden\Cache\\'.ucfirst($driver);

        if(!class_exists($driverClass)) {
            throw new Exception\Custom("Cache driver \"%s\" not found", array($driver));
        } else {
            $config = $config ?: val($driver, $options);
            self::$instances[$driver] = new $driverClass($config);
        }

        return self::$instances[$driver];
    }

    public static function clear()
    {
        self::$clear = true;
        touch(self::$clearFile);
    }

    protected static function flush()
    {
        if (isset($_GET['nocache']) || file_exists(self::$clearFile)) {
            if (!self::$clear && is_file(self::$clearFile)) {
                @unlink(self::$clearFile);
            }
            self::$clear = true;

            if (extension_loaded('Zend OPcache')) {
                opcache_reset();
            }
        }
    }

    /**
     * Retrieve a cached value entry by id.
     *
     * @param   string  $id       id of cache to entry
     * @param   string  $default  default value to return if cache miss
     * @return  mixed
     * @throws  Cache_Exception
     */
    abstract public function get($id, $default = null);

    /**
     * Set a value to cache with id and lifetime
     *
     * @param   string   $id        id of cache entry
     * @param   string   $data      data to set to cache
     * @param   integer  $lifetime  lifetime in seconds
     * @return  boolean
     */
    abstract public function set($id, $data, $lifetime = 3600);

    /**
     * Add a value to cache if a key doesn`t exists
     *
     * @param   string   $id        id of cache entry
     * @param   string   $data      data to set to cache
     * @param   integer  $lifetime  lifetime in seconds
     * @return  boolean
     */
    abstract public function add($id, $data, $lifetime = 3600);

    /**
     * Check exists cache id
     *
     * @param   string   $id        id of cache entry
     * @return  boolean
     */
    abstract public function exists($id);

    /**
     * Delete a cache entry based on id
     *
     * @param   string  $id  id to remove from cache
     * @return  boolean
     */
    abstract public function delete($id);

    /**
     * Delete all cache entries.
     *
     * Beware of using this method when
     * using shared memory cache systems, as it will wipe every
     * entry within the system for all clients.
     *
     *
     * @return  boolean
     */
    abstract public function deleteAll();
}