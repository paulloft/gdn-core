<?php
namespace Garden;

use Garden\Helpers\Arr;

class Translate {
    public static $translations = [];
    public static $defaultExtension = 'php';

    /**
     * Return translated code
     * @param string $code
     * @param string $default
     * @return string
     */
    public static function get($code, $default = null)
    {
        if (strpos($code, '@') === 0) {
            return substr($code, 1);
        }

        if (isset(self::$translations[$code])) {
            return self::$translations[$code];
        }

        if ($default !== null) {
            return $default;
        }

        return $code;
    }

    public static function load($path, $underlay = false)
    {
        $translations = Arr::load($path);

        if ($translations) {
            if ($underlay) {
                self::$translations = array_replace(self::$translations, $translations);
            } else {
                self::$translations = array_replace($translations, self::$translations);
            }
        }
    }

    public static function autoload($path = GDN_LOCALE)
    {
        $cache = Cache::instance('system');
        $locale = c('main.locale', 'en_US');
        $translations = $cache->get('translations');

        if (!$translations) {
            $locales_path = $path."/$locale/*.".self::$defaultExtension;

            $files = glob($locales_path);
            foreach ($files as $file) {
                self::load($file);
            }

            $cache->set('translations', self::$translations);
        } else {
            self::$translations = $cache->get('translations');
        }
    }
}