<?php
namespace Garden\Cache;

use \Garden\Exception;

/**
 *
 */
class Memcached extends Memcache {

    /**
     * @var \Memcached
     */
    public $cache;

    protected function connect()
    {
        if (!$this->cache) {
            if (!class_exists('Memcached')) {
                throw new Exception\Custom('Memcached extention not found');
            }

            $this->cache = new \Memcached($this->persistent);
            $this->cache->addServer($this->host, $this->port);
        }
    }

    public function set($id, $data, $lifetime = null)
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        $id = $this->fixID($id);
        $this->dirty->set($id, $data);

        return $this->cache->set($id, $data, (int)$lifetime);
    }

    public function add($id, $data, $lifetime = null)
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        $id = $this->fixID($id);

        return $this->cache->add($id, $data, (int)$lifetime);
    }

    public function getMessage()
    {
        return $this->cache->getResultMessage();
    }

    public function __destruct()
    {
        $this->cache->quit();
    }
}