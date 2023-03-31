<?php
/**
 * Created by PhpStorm.
 * User: PaulLoft
 * Date: 13.03.2019
 * Time: 23:12
 */

namespace Garden\Helpers;

use function is_array;
use function is_object;
use function is_string;

class Obj
{
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
     * @param array|Obj $object The array to get the value from.
     * @param string|int $key The array key.
     * @param mixed $default The default value to return if the key doesn't exist.
     * @return mixed The item from the array or `$default` if the array key doesn't exist.
     * @category Array Functions
     */
    public static function val($object, $key, $default = null)
    {
        if (is_array($object)) {
            return $object[$key] ?? $default;
        }

        if (is_object($object)) {
            return $object->$key ?? $default;
        }

        return $default;
    }

    /**
     * Return the value from an associative array.
     *
     * This function differs from val() in that $key can be an array that will be used to walk a nested array.
     *
     * @param array|Obj $object The array or object to search.
     * @param array|string $keys The keys or property names of the value. This can be an array or dot-seperated string.
     * @param mixed $default The value to return if the key does not exist.
     * @return mixed The value from the array or object.
     * @category Array Functions
     */
    public static function valr($object, $keys, $default = false)
    {
        if (is_string($keys)) {
            $keys = explode('.', $keys);
        }

        $value = $object;
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
}
