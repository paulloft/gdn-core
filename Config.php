<?php

namespace Garden;

use Garden\Helpers\Arr;

class Config {

    public static $cached = false;
    public static $defaultExtension = 'php';

    /**
     * @var array The config data.
     */
    protected static $data = [];
    protected static $coreConfig = GDN_SRC . '/conf';


    /**
     * Return all of the config data.
     *
     * @return array Returns an array of config data.
     */
    public static function data(): array
    {
        return self::$data;
    }

    /**
     * Get a setting from the config.
     *
     * @param string $key The config key.
     * @param mixed $default The default value if the config file doesn't exist.
     * @return mixed The value at {@link $key} or {@link $default} if the key isn't found.
     */
    public static function get(string $key, $default = null)
    {
        return Arr::path($key, self::$data, $default);
    }

    /**
     * Load configuration data from a file.
     *
     * @param string $group Name of configuration group
     * @param string $path An optional path to load the file from.
     * @param bool $underlay If true the config will be put under the current config, not over it.
     */
    public static function load(string $group, string $path, $underlay = false)
    {
        $loaded = Arr::load($path);

        if (empty($loaded)) {
            return;
        }

        if (!isset(self::$data[$group])) {
            self::$data[$group] = [];
        }

        if ($underlay) {
            $data = array_replace_recursive($loaded, self::$data[$group]);
        } else {
            $data = array_replace_recursive(self::$data[$group], $loaded);
        }

        self::$data[$group] = $data;
    }


    /**
     * Save data to the config file.
     * @param array $data The config data to save.
     * @param string $name Config name
     * @param string $extension php|json|ser|yml Config file extention
     * @param bool $rewrite Replace all data on the $data
     * @return bool
     */
    public static function save(array $data, string $name, string $extension = null, $rewrite = false): bool
    {
        $extension = $extension ?: self::$defaultExtension;
        $path = GDN_CONF . "/$name.{$extension}";

        if ($rewrite) {
            $config = $data;
        } else {
            $config = Arr::load($path);
            $config = array_replace_recursive($config, $data);
        }

        // Remove null config values.
        $config = array_filter($config, function ($value) {
            return $value !== null;
        });

        $result = Arr::save($config, $path);

        Cache::instance('system')->delete('config-autoload');

        return $result;
    }

    public static function loadDir($path)
    {
        $files = glob($path . '/*.' . self::$defaultExtension);
        foreach ($files as $file) {
            $info = pathinfo($file);
            $group = val('filename', $info);

            if ($group) {
                self::load($group, $file);
            }
        }
    }

    /**
     * Autoad all config files from $path
     * @param string $path
     */
    public static function autoload($path = GDN_CONF)
    {
        $cached = Gdn::cache('system')->get('config-autoload');
        if ($cached) {
            self::$data = $cached;
        } else {
            // load default configs from $coreConfig
            if ($path !== self::$coreConfig) {
                self::autoload(self::$coreConfig);
            }

            self::loadDir($path);
        }
    }

    /**
     * caching all configs data
     */
    public static function cache()
    {
        if (!Cache::instance('system')->get('config-autoload')) {
            Cache::instance('system')->set('config-autoload', self::$data);
        }
    }
}
