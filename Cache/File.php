<?php

namespace Garden\Cache;

use Garden\Cache;
use Garden\Gdn;
use function count;

/**
 *
 */
class File extends Cache {
    public $lifetime;
    public $cacheDir;

    public $packFunction = 'serialize';
    public $unpackFunction = 'unserialize';

    public $extention = '.cache';

    protected $dirty;

    public function __construct($config)
    {
        $this->lifetime = $config['defaultLifetime'] ?? parent::DEFAULT_LIFETIME;
        $this->packFunction = $config['packFunction'] ?? $this->packFunction;
        $this->unpackFunction = $config['unpackFunction'] ?? $this->unpackFunction;

        $cacheDir = $config['cacheDir'] ?? false;

        $this->cacheDir = $cacheDir ? realpath(PATH_ROOT . "/$cacheDir") : GDN_CACHE;

        /** @see https://github.com/kalessil/phpinspectionsea/blob/master/docs/probable-bugs.md#mkdir-race-condition */
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true) && is_dir($this->cacheDir);
        }

        $this->dirty = Cache::instance('dirty');
    }

    /**
     * Replaces troublesome characters with underscores.
     *
     * @param string $id id of cache to sanitize
     * @return  string
     */
    protected function fixID($id): string
    {
        // Change slashes and spaces to underscores
        return str_replace(['/', '\\', ' '], '_', $id);
    }

    protected function getFileName($id): string
    {
        $id = $this->fixID($id);
        $salt = substr(md5($id), 0, 10);

        return "$id-$salt.cache";
    }

    /**
     * Retrieve a cached value entry by id.
     *
     * @param string $id id of cache to entry
     * @param string $default default value to return if cache miss
     * @return  mixed
     */
    public function get($id, $default = null)
    {
        $fileName = $this->getFileName($id);
        $data = null;

        if (!self::$clear && !$data = $this->dirty->get($fileName)) {

            $file = "{$this->cacheDir}/$fileName";

            if (!is_file($file)) {
                return $default;
            }

            $unpackFunction = $this->unpackFunction;

            $result = file_get_contents($file);
            $result = $unpackFunction($result);
            $expire = $result['expire'] ?? 0;
            $data = $result['data'] ?? null;

            if ($expire !== false && time() > $expire) {
                $this->delete($id);
                return $default;
            }

            //save to temporary cache
            $this->dirty->add($fileName, $data);
        }

        return $data ?: $default;
    }

    public function exists($id): bool
    {
        $file = "{$this->cacheDir}/" . $this->getFileName($id);

        return (!self::$clear && is_file($file));
    }

    /**
     * Set a value to cache with id and lifetime
     *
     * @param string $id id of cache entry
     * @param string $data data to set to cache
     * @param integer $lifetime lifetime in seconds
     * @return  boolean
     */
    public function set($id, $data, $lifetime = null): bool
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        $cacheData = [
            'expire' => $lifetime === null ? false : (time() + (int)$lifetime),
            'data' => $data
        ];
        $packFunction = $this->packFunction;
        $cacheData = $packFunction($cacheData);

        $fileName = $this->getFileName($id);
        $cachePath = "{$this->cacheDir}/$fileName";

        $result = file_put_contents($cachePath, $cacheData, LOCK_EX);
        chmod($cachePath, 0664);

        $this->dirty->set($fileName, $data);

        return (bool)$result;
    }

    public function add($id, $data, $lifetime = null): bool
    {
        if (!$this->exists($id)) {
            return $this->set($id, $data, $lifetime);
        }

        return false;
    }

    /**
     * Delete a cache entry based on id
     *
     * @param string $id id to remove from cache
     * @return  boolean
     */
    public function delete($id): bool
    {
        $return = unlink("{$this->cacheDir}/" . $this->getFileName($id));
        self::reset_opcache();

        return $return;
    }

    /**
     * Delete all cache entries.
     *
     * Beware of using this method when
     * using shared memory cache systems, as it will wipe every
     * entry within the system for all clients.
     *
     * @return  bool
     */
    public function deleteAll(): bool
    {
        $files = glob("{$this->cacheDir}/*{$this->extention}");

        $deleted = 0;
        foreach ($files as $file) {
            if (!is_dir($file) && unlink($file)) {
                $deleted++;
            }
        }
        self::reset_opcache();

        return count($files) === $deleted;
    }
}