<?php
namespace Garden\Cache;
use Garden\Helpers\Arr;

/**
* 
*/
class Dirty extends \Garden\Cache
{
    protected $config;
    protected $data = [];

    public function __construct(array $config = []){
        $this->config = $config;
    }

    public function get($id, $default = null)
    {
        return val($id, $this->data, $default);
    }
    
    public function set($id, $data, $lifetime = 3600): bool
    {
        $this->data[$id] = $data;
        return true;
    }

    public function add($id, $data, $lifetime = 3600): bool
    {
        if(!isset($this->data[$id])) {
            $this->data[$id] = $data;
            return true;
        }

        return false;
    }

    public function exists($id): bool
    {
        return isset($this->data[$id]);
    }

    public function delete($id): bool
    {
        unset($this->data[$id]);
        return true;
    }

    public function deleteAll(): bool
    {
        $this->data = [];
        return true;
    }

    public function cacheGet($key, callable $cache_cb) {
        $cache_path = GDN_CACHE."/$key.json";
        if(!$result = $this->get($key)) {
            if (file_exists($cache_path)) {
                $result = Arr::load($cache_path);
            } else {
                $result = $cache_cb();
                Arr::save($result, $cache_path);
            }
            $this->add($key, $result);
        }
        return $result;
    }
}