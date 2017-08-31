<?php
namespace Garden\Cache;

use \Garden\Exception;

class Redis extends \Garden\Cache
{
    /**
     * @var \Redis
     */
    public $cache;

    protected $lifetime;

    protected $prefix = 'gdn_';
    protected $host = 'localhost';
    protected $port = 6379;
    protected $timeout = 0;
    protected $reserved;
    protected $retry_interval = 0;

    protected $salt;
    protected $dirty;

    private $_igbinary;

    public function __construct($config)
    {
        $this->lifetime = val('defaultLifetime', $config, parent::DEFAULT_LIFETIME);
        $this->host = val('host', $config, $this->host);
        $this->port = val('port', $config, $this->port);
        $this->prefix = val('keyPrefix', $config, $this->prefix);

        $this->timeout = val('timeout', $config, $this->timeout);
        $this->reserved = val('reserved', $config, $this->reserved);
        $this->retry_interval = val('retry_interval', $config, $this->retry_interval);

        $this->cache = val('connection', $config, null);

        $this->salt = c('main.hashsalt', 'gdn');
        $this->dirty = \Garden\Gdn::dirtyCache();

        $this->_igbinary = function_exists('igbinary_serialize');

        $this->connect();
    }

    protected function connect()
    {
        if (!$this->cache) {
            if (!class_exists('Redis')) {
                throw new Exception\Custom('Redis extention not found');
            }

            $this->cache = new \Redis();
            if (!$this->cache->connect($this->host, $this->port, $this->timeout, $this->reserved, $this->retry_interval)) {
                throw new Exception\Custom($this->cache->getLastError());
            }
        }
    }

    protected function fixID($id)
    {
        return $this->prefix . md5($id . $this->salt);
    }

    public function get($id, $default = null)
    {
        $id = $this->fixID($id);
        $result = null;

        if (!self::$clear && !$result = $this->dirty->get($id)) {
            $result = $this->unserialize($this->cache->get($id));
            $this->dirty->add($id, $result);
        }
        return $result ?: $default;
    }

    public function set($id, $data, $lifetime = null)
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        $id = $this->fixID($id);
        $this->dirty->set($id, $data);

        return $this->cache->setex($id, (int)$lifetime, $this->serialise($data));
    }

    public function add($id, $data, $lifetime = null)
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        $id = $this->fixID($id);

        return $this->cache->exists($id) ? $this->cache->set($id, $data, (int)$lifetime) : false;
    }

    public function exists($id)
    {
        $id = $this->fixID($id);
        return (bool)$this->cache->exists($id);
    }

    public function delete($id)
    {
        $id = $this->fixID($id);
        $this->dirty->delete($id);
        $this->cache->delete($id);
    }

    public function deleteAll()
    {
        $this->cache->flushAll();
    }


    public function __destruct()
    {
        $this->cache->close();
    }

    protected function serialise($data)
    {
        if ($this->_igbinary) {
            return igbinary_serialize($data);
        }

        return serialize($data);
    }

    protected function unserialize($data)
    {
        if ($this->_igbinary) {
            return igbinary_unserialize($data);
        }

        return unserialize($data);
    }
}