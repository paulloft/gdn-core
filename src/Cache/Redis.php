<?php

namespace Garden\Cache;

use Garden\Cache;
use Garden\Config;
use Garden\Exception;
use Garden\Gdn;
use function function_exists;

class Redis extends Cache {
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

    /**
     * Redis constructor.
     * @param $config
     * @throws Exception\Error
     */
    public function __construct($config)
    {
        $this->lifetime = $config['defaultLifetime'] ?? parent::DEFAULT_LIFETIME;
        $this->host = $config['host'] ?? $this->host;
        $this->port = $config['port'] ?? $this->port;
        $this->prefix = $config['keyPrefix'] ?? $this->prefix;

        $this->timeout = $config['timeout'] ?? $this->timeout;
        $this->reserved = $config['reserved'] ?? $this->reserved;
        $this->retry_interval = $config['retry_interval'] ?? $this->retry_interval;

        $this->cache = $config['connection'] ?? null;

        $this->salt = Config::get('main.hashsalt', 'gdn');
        $this->dirty = Cache::instance('dirty');

        $this->_igbinary = function_exists('igbinary_serialize');

        $this->connect();
    }

    /**
     * @throws Exception\Error
     */
    protected function connect()
    {
        if (!$this->cache) {
            if (!class_exists('Redis')) {
                throw new Exception\Error('Redis extention not found');
            }

            $this->cache = new \Redis();
            if (!$this->cache->connect($this->host, $this->port, $this->timeout, $this->reserved, $this->retry_interval)) {
                throw new Exception\Error($this->cache->getLastError());
            }
        }
    }

    protected function fixID($id): string
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

    public function set($id, $data, $lifetime = null): bool
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        $id = $this->fixID($id);
        $this->dirty->set($id, $data);

        return $this->cache->setex($id, (int)$lifetime, $this->serialise($data));
    }

    public function add($id, $data, $lifetime = null): bool
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        $id = $this->fixID($id);

        return $this->cache->exists($id) ? $this->cache->set($id, $data, (int)$lifetime) : false;
    }

    public function exists($id): bool
    {
        $id = $this->fixID($id);
        return (bool)$this->cache->exists($id);
    }

    public function delete($id): bool
    {
        $id = $this->fixID($id);
        $this->dirty->delete($id);
        return $this->cache->delete($id);
    }

    public function deleteAll(): bool
    {
        return $this->cache->flushAll();
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