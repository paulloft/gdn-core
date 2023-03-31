<?php
namespace Garden\Cache;

use Exception;
use Garden\Cache;

class System extends Cache
{
    protected $dirty;

    public function __construct($config)
    {
        $this->dirty = Cache::instance('dirty');
    }

    protected function getFile($id): string
    {
        return "$id.json";
    }

    public function get($id, $default = null)
    {
        $file = $this->getFile($id);
        $data = $this->dirty->get($file);

        if (!self::$clear && !$data) {

            $filePath = GDN_CACHE . '/' . $file;

            if (!is_file($filePath)) {
                return $default;
            }

            try {
                $result = file_get_contents($filePath);
                $data = json_decode($result, true);

                //save to temporary cache
                $this->dirty->add($file, $data);
            } catch (Exception $exception) {
                //do nothing
            }
        }

        return $data ?: $default;
    }

    public function set($id, $data, $lifetime = 0): bool
    {
        $cacheData = json_encode($data, JSON_PRETTY_PRINT);

        $file = $this->getFile($id);

        $filePath = GDN_CACHE . '/' . $file;

        try {
            $result = file_put_contents($filePath, $cacheData);
            chmod($filePath, 0664);
        } catch (Exception $exception) {
            $result = null;
        }

        $this->dirty->set($file, $data);

        return (bool)$result;
    }

    public function deleteAll(): bool
    {
        $dir = scandir(GDN_CACHE, SCANDIR_SORT_NONE);
        $regexp = '/' . $this->getFile('([\w-\_]+)') . '/';
        foreach ($dir as $filename) {
            if (preg_match($regexp, $filename)) {
                $file = GDN_CACHE . '/' . $filename;
                if (is_file($file)) {
                    @unlink($file);
                }
            }

        }
        self::reset_opcache();
        return true;
    }

    public function delete($id): bool
    {
        $file = GDN_CACHE . '/' . $this->getFile($id);
        if (!is_dir($file)) {
            @unlink($file);
        }
        self::reset_opcache();
        return true;
    }

    public function add($id, $data, $lifetime = null): bool
    {
        return false;
    }

    public function exists($id): bool
    {
        return false;
    }
}
