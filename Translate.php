<?php
namespace Garden;

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
        } elseif (isset(self::$translations[$code])) {
            return self::$translations[$code];
        } elseif ($default !== null) {
            return $default;
        } else {
            return $code;
        }
    }

    public static function load($path, $underlay = false)
    {
        $locale = c('main.locale', 'en_US');
        $locale_path = "$path/$locale.".self::$defaultExtension;

        $translations = array_load($path);

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