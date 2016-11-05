<?php
/**
* @author Todd Burry <todd@vanillaforums.com>
* @copyright 2009-2014 Vanilla Forums Inc.
* @license MIT
*/

/**
 * Format a callback function as a string.
 *
 * @param callable $callback The callback to format.
 * @return string Returns a string representation of the callback.
 * @return string Returns the callback as a string.
 */
function format_callback(callable $callback) {
    if (is_string($callback)) {
        return $callback.'()';
    } elseif (is_array($callback)) {
        if (is_object($callback[0])) {
            return get_class($callback[0]).'->'.$callback[1].'()';
        } else {
            return $callback[0].'::'.$callback[1].'()';
        }
    } elseif ($callback instanceof Closure) {
        return 'closure()';
    }
    return '';
}

/**
 * Format a span of time that comes from a timer.
 *
 * @param float $seconds The number of seconds that elapsed.
 * @return string
 * @see microtime()
 */
function format_duration($seconds) {
    if ($seconds < 1.0e-3) {
        $n = number_format($seconds * 1.0e6, 0);
        $sx = 'μs';
    } elseif ($seconds < 1) {
        $n = number_format($seconds * 1000, 0);
        $sx = 'ms';
    } elseif ($seconds < 60) {
        $n = number_format($seconds, 1);
        $sx = 's';
    } elseif ($seconds < 3600) {
        $n = number_format($seconds / 60, 1);
        $sx = 'm';
    } elseif ($seconds < 86400) {
        $n = number_format($seconds / 3600, 1);
        $sx = 'h';
    } else {
        $n = number_format($seconds / 86400, 1);
        $sx = 'd';
    }

    $result = rtrim($n, '0.').$sx;
    return $result;
}

/**
 * Format a number of bytes with the largest unit.
 *
 * @param int $bytes The number of bytes.
 * @param int $precision The number of decimal places in the formatted number.
 * @return string the formatted filesize.
 */
function format_filesize($bytes, $precision = 1) {
    $units = array('B', 'K', 'M', 'G', 'T');

    $bytes = max((int)$bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    $result = round($bytes, $precision).$units[$pow];
    return $result;
}

/**
 * Unformat a file size that was formatted using {@link format_filesize()}.
 *
 * @param $str The formatted file suze to unformat.
 * @return int Returns the file size in bytes.
 */
function unformat_filesize($str) {
    $units = array('B' => 1, 'K' => 1 << 10, 'M' => 1 << 20, 'G' => 1 << 30, 'T' => 1 << 40);

    if (preg_match('/([0-9.]+)\s*([A-Z]*)/i', $str, $matches)) {
        $number = floatval($matches[1]);
        $unit = strtoupper(substr($matches[2], 0, 1));
        $mult = val($unit, $units, 1);

        $result = round($number * $mult, 0);
        return $result;
    } else {
        return null;
    }
}

$transliterations = array('–' => '-', '—' => '-', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae', 'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D', 'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K', 'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O', 'Œ' => 'OE', 'Ŕ' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S', 'Ş' => 'S', 'Ŝ' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ū' => 'U', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U', 'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z', 'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a', 'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ū' => 'u', 'ů' => 'u', 'ű' => 'u', 'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss', 'ſ' => 'ss', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'ș' => 's', 'ț' => 't', 'Ț' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya');

/**
 * Generate a url friendly slug from a string.
 *
 * @param string $str A string to be formatted.
 * @return string
 * @global array $transliterations An array of translations from other scripts into url friendly characters.
 */
function format_slug($str) {
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