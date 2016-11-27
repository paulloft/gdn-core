<?php
namespace Garden\Cache;

class System extends \Garden\Cache
{
    protected $dirty;

    public function __construct($config)
    {
        $this->dirty = \Garden\Gdn::dirtyCache();

        if(!is_dir(GDN_CACHE)) {
            @mkdir(GDN_CACHE, 0777, true);
        }
    }

    protected function getFile($id)
    {
        return "$id.json";
    }

    public function get($id, $default = false)
    {
        $file = $this->getFile($id);
        $data = $this->dirty->get($file);

        if(!self::$clear && !$data) {

            $filePath = GDN_CACHE.'/'.$file;

            if(!is_file($filePath)) {
                return $default;
            }

            try {
                $result = file_get_contents($filePath);
                $data = json_decode($result, true);

                //save to temporary cache
                $this->dirty->add($file, $data);
            } catch (\Exception $exception) {
            }
        }

        return $data ?: $default;
    }

    public function set($id, $data, $lifetime = 0)
    {
        $cacheData = json_encode($data, JSON_PRETTY_PRINT);

        $file = $this->getFile($id);

        $filePath = GDN_CACHE.'/'.$file;

        try {
            $result = file_put_contents($filePath, $cacheData);
            chmod($filePath, 0664);
        } catch (\Exception $exception) {
            $result = false;
        }

        $this->dirty->set($file, $data);

        return (bool)$result;
    }

    public function deleteAll()
    {
        $dir = scandir(GDN_CACHE);
        $regexp = '/'.$this->getFile('([\w-_]+)').'/';
        foreach ($dir as $filename) {
            if(preg_match($regexp, $filename)) {
                $file = GDN_CACHE.'/'.$filename;
                if(!is_dir($file)) {
                    @unlink($file);
                }
            }

        }
    }

    public function delete($id)
    {
        $file = GDN_CACHE.'/'.$this->getFile($id);
        if(!is_dir($file)) {
            @unlink($file);
        }
    }

    public function add($id, $data, $lifetime = null){}

    public function exists($id){}
}