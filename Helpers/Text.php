<?php
/**
 * @author paulloft
 * @license MIT
 */

namespace Garden\Helpers;

use function count;
use function strlen;

class Text {

    public static $transliterations = [
        '–' => '-', '—' => '-', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Å' => 'A',
        'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae', 'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C',
        'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D', 'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ē' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G',
        'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I',
        'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K',
        'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O',
        'Œ' => 'OE', 'Ŕ' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S', 'Ş' => 'S', 'Ŝ' => 'S', 'Ť' => 'T',
        'Ţ' => 'T', 'Ŧ' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ū' => 'U', 'Ů' => 'U',
        'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U', 'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y',
        'Ź' => 'Z', 'Ž' => 'Z', 'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
        'ä' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a', 'æ' => 'ae', 'ç' => 'c', 'ć' => 'c',
        'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e',
        'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ƒ' => 'f',
        'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h', 'ì' => 'i', 'í' => 'i',
        'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij',
        'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'ŀ' => 'l',
        'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o',
        'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe',
        'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue',
        'ū' => 'u', 'ů' => 'u', 'ű' => 'u', 'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y',
        'ÿ' => 'y', 'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss', 'ſ' => 'ss',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH',
        'З' => 'Z', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P',
        'Р' => 'R', 'С' => 'S', 'ș' => 's', 'ț' => 't', 'Ț' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H',
        'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E',
        'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
        'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];

    /**
     * Format a span of time that comes from a timer.
     *
     * @param float $seconds The number of seconds that elapsed.
     * @return string
     * @see microtime()
     */
    public static function duration($seconds): string
    {
        if ($seconds < 1.0e-3) {
            $n = number_format($seconds * 1.0e6, 0);
            $sx = self::translate('microseconds', 'μs');
        } elseif ($seconds < 1) {
            $n = number_format($seconds * 1000, 0);
            $sx = self::translate('miliseconds', 'ms');
        } elseif ($seconds < 60) {
            $n = number_format($seconds, 1);
            $sx = self::translate('seconds', 's');
        } elseif ($seconds < 3600) {
            $n = number_format($seconds / 60, 1);
            $sx = self::translate('minutes', 'm');
        } elseif ($seconds < 86400) {
            $n = number_format($seconds / 3600, 1);
            $sx = self::translate('hours', 'h');
        } else {
            $n = number_format($seconds / 86400, 1);
            $sx = self::translate('days', 'd');
        }

        return rtrim($n, '0.') . ' ' . $sx;
    }

    /**
     * Generate a url friendly slug from a string.
     *
     * @param string $str A string to be formatted.
     * @return string
     * @global array $transliterations An array of translations from other scripts into url friendly characters.
     */
    public static function translit(string $str): string
    {
        global $transliterations;

        $str = trim($str);
        $str = strip_tags(html_entity_decode($str, ENT_COMPAT, 'UTF-8')); // remove html tags
        $str = strtr($str, $transliterations); // transliterate known characters
        $str = preg_replace('`([^\PP.\-_])`u', '', $str); // get rid of punctuation
        $str = preg_replace('`([^\PS+])`u', '', $str); // get rid of symbols
        $str = preg_replace('`[\s\-/+.]+`u', '-', $str); // replace certain characters with dashes
        $str = rawurlencode(strtolower($str));
        $str = trim($str, '.-');
        return $str;
    }

    /**
     * safe output to form value
     * @param string $string
     * @return string
     */
    public static function safe($string): string
    {
        return nl2br(htmlspecialchars($string, ENT_QUOTES));
    }

    /**
     * outputs the correct word ending for numbers
     * @param int $num
     * @param array $exp ['for a value ending at 0', 'for a value ending at 1', 'for a value ending at 2']
     * @return string
     */
    public static function declension($num, array $exp): string
    {
        if (count($exp) < 3) {
            return '';
        }

        $num = (($num < 0) ? $num - $num * 2 : $num) % 100;
        $dig = ($num > 20) ? $num % 10 : $num;

        if ($dig === 1) {
            return $exp[1];
        }

        if ($dig > 4 || $dig < 1) {
            return $exp[0];
        }

        return $exp[2];
    }

    /**
     * Cuts the text along the length and adds ...
     * @param $string
     * @param int $limit
     * @param string $endLine
     * @return string
     */
    public static function substr($string, $limit = 120, $endLine = '… '): string
    {
        $string = strip_tags($string);
        $string = mb_substr($string, 0, $limit);
        $string = rtrim($string, '!,.-');
        $string = mb_substr($string, 0, strrpos($string, ' '));

        return $string . $endLine;
    }

    /**
     * money format (1 000 000.00)
     * @param int $cost
     * @return string
     */
    public static function formatCost($cost): string
    {
        return $cost ? number_format($cost, 2, '.', ' ') : 0;
    }

    /**
     * Format a number of bytes with the largest unit.
     *
     * @param int $bytes The number of bytes.
     * @param int $precision The number of decimal places in the formatted number.
     * @return string the formatted filesize.
     */
    public static function formatFilesize($bytes, $precision = 1): string
    {
        $units = ['B', 'K', 'M', 'G', 'T'];

        $bytes = max((int)$bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . $units[$pow];
    }

    /**
     * multibyte ucfirst
     * @param $str
     * @return string
     */
    public static function ucfirst($str): string
    {
        $str = mb_strtolower($str);
        $fc = mb_strtoupper(mb_substr($str, 0, 1));
        return $fc . mb_substr($str, 1);
    }

    /**
     * Strip a substring from the beginning of a string.
     *
     * @param string $mainstr The main string to look at (the haystack).
     * @param string $substr The substring to search trim (the needle).
     * @return string
     *
     * @category String Functions
     */
    public static function ltrimSubstr($mainstr, $substr): string
    {
        if (strncasecmp($mainstr, $substr, strlen($substr)) === 0) {
            return substr($mainstr, strlen($substr));
        }
        return $mainstr;
    }

    /**
     * Strip a substring rom the end of a string.
     *
     * @param string $mainstr The main string to search (the haystack).
     * @param string $substr The substring to trim (the needle).
     * @return string Returns the trimmed string or {@link $mainstr} if {@link $substr} was not found.
     * @category String Functions
     */
    public static function rtrimSubstr($mainstr, $substr): string
    {
        if (strcasecmp(substr($mainstr, -strlen($substr)), $substr) === 0) {
            return substr($mainstr, 0, -strlen($substr));
        }
        return $mainstr;
    }

    /**
     * Returns whether or not a string begins with another string.
     *
     * This function is not case-sensitive.
     *
     * @param string $haystack The string to test.
     * @param string $needle The substring to test against.
     * @return bool Whether or not `$string` begins with `$with`.
     * @category String Functions
     */
    public static function strBegins($haystack, $needle): bool
    {
        return strncasecmp($haystack, $needle, strlen($needle)) === 0;
    }

    /**
     * Returns whether or not a string ends with another string.
     *
     * This function is not case-sensitive.
     *
     * @param string $haystack The string to test.
     * @param string $needle The substring to test against.
     * @return bool Whether or not `$string` ends with `$with`.
     * @category String Functions
     */
    public static function strEnds($haystack, $needle): bool
    {
        return strcasecmp(substr($haystack, -strlen($needle)), $needle) === 0;
    }

    /**
     * Force a string to look like an ip address (v4).
     *
     * @param string $ip The ip string to look at.
     * @return string|null The ipv4 address or null if {@link $ip} is empty.
     */
    public static function ipv4($ip): string
    {
        if (!$ip) {
            return null;
        }

        if (strpos($ip, ',') !== false) {
            $ip = substr($ip, 0, strpos($ip, ','));
        }

        // Make sure we have a valid ip.
        if (preg_match('`(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})`', $ip, $m)) {
            $ip = $m[1];
        } elseif ($ip === '::1') {
            $ip = '127.0.0.1';
        } else {
            $ip = '0.0.0.0'; // unknown ip
        }
        return $ip;
    }

    /**
     * @param $code
     * @param string $default
     * @return string
     */
    public static function translate($code, $default = null): string
    {
        if (class_exists('\\Garden\\Translate')) {
            \Garden\Translate::get($code, $default);
        }

        return $default;
    }

}