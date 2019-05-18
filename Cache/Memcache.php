<?php

namespace Garden\Cache;

use Garden\Cache;
use Garden\Config;
use Garden\Exception;
use Garden\Gdn;

/**
 *
 */
class Memcache extends Cache {
    protected $lifetime;

    protected $host = 'localhost';
    protected $port = 11211;
    protected $persistent = false;
    protected $prefix = 'gdn_';

    protected $salt;
    protected $dirty;

    /**
     * @var \Memcache
     */
    public $cache;

    /**
     * Memcache constructor.
     * @param array $config
     * @throws Exception\Error
     */
    public function __construct(array $config = [])
    {
        $this->lifetime = $config['defaultLifetime'] ?? parent::DEFAULT_LIFETIME;
        $this->persistent = $config['persistent'] ?? $this->persistent;

        $this->host = $config['host'] ?? $this->host;
        $this->port = $config['port'] ?? $this->port;
        $this->prefix = $config['keyPrefix'] ?? $this->prefix;
        $this->cache = $config['connection'] ?? null;

        $this->salt = Config::get('main.hashsalt', 'gdn');

        $this->dirty = Gdn::dirtyCache();

        $this->connect();
    }

    /**
     * @throws Exception\Error
     */
    protected function connect()
    {
        if (!$this->cache) {
            if (!class_exists('memcache')) {
                throw new Exception\Error('memcache extention not found');
            }

            $this->cache = new \Memcache();
            $this->cache->addServer($this->host, $this->port, $this->persistent);
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
            $result = $this->cache->get($id);
            //save to temporary cache
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

        return $this->cache->set($id, $data, MEMCACHE_COMPRESSED, (int)$lifetime);
    }

    public function add($id, $data, $lifetime = null): bool
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        $id = $this->fixID($id);

        return $this->cache->add($id, $data, MEMCACHE_COMPRESSED, (int)$lifetime);
    }

    public function exists($id): bool
    {
        return (bool)$this->get($id);
    }

    public function delete($id): bool
    {
        $id = $this->fixID($id);
        $this->dirty->delete($id);

        return $this->cache->delete($id);
    }

    public function deleteAll(): bool
    {
        return $this->cache->flush();
    }


    public function __destruct()
    {
        $this->cache->close();
    }
}