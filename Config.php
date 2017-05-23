<?php

namespace Garden;

class Config {

    public static $cached = false;
    public static $defaultExtension = 'php';

    /**
     * @var array The config data.
     */
    protected static $data = [];

    /**
     * @var string The default path to load/save to.
     */
    protected static $defaultPath;
    protected static $coreConfig = GDN_SRC.'/conf';

    /**
     * Get or set the default path.
     *
     * @param string $value Pass a value to set a new default path.
     * @return string Returns the current default path.
     */
    public static function defaultPath($value = '') {
        if ($value) {
            self::$defaultPath = $value;
        } elseif (!self::$defaultPath) {
            self::$defaultPath = GDN_CONF.'/config.php';
        }
        return self::$defaultPath;
    }

    /**
     * Return all of the config data.
     *
     * @return array Returns an array of config data.
     */
    public static function data() {
        return self::$data;
    }

    /**
     * Get a setting from the config.
     *
     * @param string $group Name of configuration group
     * @param string $key The config key.
     * @param mixed $default The default value if the config file doesn't exist.
     * @return mixed The value at {@link $key} or {@link $default} if the key isn't found.
     * @see \config()
     */
    public static function get($group, $key = false, $default = null) {
        if (array_key_exists($group, self::$data)) {
            $data = self::$data[$group];
        } else {
            return $default;
        }

        if($key === false) {
            return $data;
        }

        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * Load configuration data from a file.
     *
     * @param string $group Name of configuration group
     * @param string $path An optional path to load the file from.
     * @param string $path If true the config will be put under the current config, not over it.
     */
    public static function load($group, $path = '', $underlay = false) {
        if (!$path) {
            $path = self::defaultPath();
        }

        $loaded = array_load($path);

        if (empty($loaded)) {
            return;
        }

        if (!isset(self::$data[$group])) {
            self::$data[$group] = [];
        }

        if ($underlay) {
            $data = array_replace($loaded, self::$data[$group]);
        } else {
            $data = array_replace(self::$data[$group], $loaded);
        }
        self::$data[$group] = $data;
    }


    /**
     * Save data to the config file.
     * @param array $data The config data to save.
     * @param string $name Config name
     * @param bool $extension php|json|ser|yml Config file extention
     * @param bool $rewrite Replace all data on the $data
     * @return bool
     */
    public static function save(array $data, $name, $extension = false, $rewrite = false)
    {
        $extension = $extension ?: self::$defaultExtension;
        $path = GDN_CONF . "/$name.{$extension}";

        if (!$rewrite) {
            $config = self::get($name, false, []);
            $config = array_replace($config, $data);
        } else {
            $config = $data;
        }

        // Remove null config values.
        $config = array_filter($config, function ($value) {
            return $value !== null;
        });

        $result = array_save($config, $path);

        Cache::instance('system')->delete('config-autoload');

        return $result;
    }

    public static function loadDir($path)
    {
        $files = glob($path.'/*.'.self::$defaultExtension);
        foreach ($files as $file) {
            $info = pathinfo($file);
            $group = val('filename', $info);

            if($group) {
                self::load($group, $file);
            }
        }
    }

    /**
     * Autoad all config files from $path
     * @param string $path
     */
    public static function autoload($path = GDN_CONF) {
        $cached = Gdn::cache('system')->get('config-autoload');
        if(!$cached) {
            // load default configs from $coreConfig
            if($path !== self::$coreConfig) {
                Config::autoload(self::$coreConfig);
            }

            self::loadDir($path);
        } else {
            self::$data = $cached; 
        }
    }

    /**
     * caching all configs data
     */
    public static function cache() {
        if(!Cache::instance('system')->get('config-autoload')) {
            Cache::instance('system')->set('config-autoload', self::$data);
        }
    }
}
