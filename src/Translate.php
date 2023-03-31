<?php

namespace Garden;

use Garden\Helpers\Arr;

class Translate
{
    public const DATE_FORMATS = ['D', 'l', 'F', 'M'];

    public static $translations = [];
    public static $defaultExtension = 'php';

    /**
     * A version of {@link sprintf()} That translates the string format.
     *
     * @param string $code The format translation code.
     * @param mixed ...$args The arguments to pass to {@link sprintf()}.
     * @return string The translated string.
     */
    public static function getSprintf($code, ...$args): string
    {
        return vsprintf(self::get($code), $args);
    }

    /**
     * Return translated code
     *
     * @param string $code
     * @param string $default
     * @return string
     */
    public static function get($code, $default = null): string
    {
        if (strncmp($code, '@', 1) === 0) {
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

    /**
     * A version of {@link vsprintf()} That translates the string format.
     *
     * @param string $code The format translation code.
     * @param array $args The arguments to pass to {@link sprintf()}.
     * @return string The translated string.
     */
    public static function getVsprintf($code, array $args, $default = null): string
    {
        return vsprintf(self::get($code, $default), $args);
    }

    /**
     * Autoload transaltions from path
     *
     * @param string $path
     */
    public static function autoload($path = GDN_LOCALE)
    {
        $cache = Cache::instance('system');
        $locale = Config::get('main.locale', 'en_US');
        $translations = $cache->get('translations');

        if ($translations) {
            self::$translations = $cache->get('translations');
        } else {
            $locales_path = "$path/$locale/*." . self::$defaultExtension;

            $files = glob($locales_path);
            foreach ($files as $file) {
                self::load($file);
            }

            $cache->set('translations', self::$translations);
        }
    }

    /**
     * Load transaltions from file
     *
     * @param $path
     * @param bool $underlay
     */
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

    /**
     * @param \DateTime $dateTime
     * @param string $format
     * @return string
     */
    public static function date(\DateTime $dateTime, string $format): string
    {
        return preg_replace_callback('/\w/', static function ($matches) use ($dateTime) {
            $format = reset($matches);
            $result = $dateTime->format($format);

            if (in_array($format, static::DATE_FORMATS)) {
                $result = static::get($result);
            }

            return $result;
        }, $format);
    }
}
