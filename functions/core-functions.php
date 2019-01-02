<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

/**
 * An alias of {@link config()}.
 *
 * @param string $key The config key.
 * @param string $default The default value if the config setting isn't available.
 * @return mixed The config value.
 * @see \Garden\Config:get()
 */
function c($key = false, $default = null)
{
    $data = Garden\Config::data();
    return valr($key, $data, $default);
}



/**
 * Translate a string.
 *
 * @param string $code The translation code.
 * @param string $default The default if the translation is not found.
 * @return string The translated string.
 *
 * @category String Functions
 * @category Localization Functions
 */
function t($code, $default = null)
{
    return \Garden\Translate::get($code, $default);
}


/**
 * Safely get a value out of an array.
 *
 * This function will always return a value even if the array key doesn't exist.
 * The val() function is one of the biggest workhorses of Vanilla and shows up a lot throughout other code.
 * It's much preferable to use this function if your not sure whether or not an array key exists rather than
 * using @ error suppression.
 *
 * This function uses optimizations found in the [facebook libphputil library](https://github.com/facebook/libphutil).
 *
 * @param string|int $key The array key.
 * @param array|object $array The array to get the value from.
 * @param mixed $default The default value to return if the key doesn't exist.
 * @return mixed The item from the array or `$default` if the array key doesn't exist.
 * @category Array Functions
 */
function val($key, $array, $default = false)
{
    if (is_array($array)) {
        // isset() is a micro-optimization - it is fast but fails for null values.
        if (isset($array[$key])) {
            return $array[$key];
        }

        // Comparing $default is also a micro-optimization.
        if ($default === null || array_key_exists($key, $array)) {
            return null;
        }
    } elseif (is_object($array)) {
        if (isset($array->$key)) {
            return $array->$key;
        }

        if ($default === null || property_exists($array, $key)) {
            return null;
        }
    }

    return $default;
}

/**
 * Return the value from an associative array.
 *
 * This function differs from val() in that $key can be an array that will be used to walk a nested array.
 *
 * @param array|string $keys The keys or property names of the value. This can be an array or dot-seperated string.
 * @param array|object $array The array or object to search.
 * @param mixed $default The value to return if the key does not exist.
 * @return mixed The value from the array or object.
 * @category Array Functions
 */
function valr($keys, $array, $default = false)
{
    if (is_string($keys)) {
        $keys = explode('.', $keys);
    }

    $value = $array;
    foreach ($keys as $subKey) {
        if (is_array($value) && isset($value[$subKey])) {
            $value = $value[$subKey];
        } elseif (is_object($value) && isset($value->$subKey)) {
            $value = $value->$subKey;
        } else {
            return $default;
        }
    }
    return $value;
}

if (!function_exists('p')) {
    function p(...$args) {
        foreach ($args as $a) {
            \Dumphper\Dumphper::dump($a);
        }
    }
}

if (!function_exists('d')) {
    function d(...$args) {
        p(...$args);
        exit();
    }
}
