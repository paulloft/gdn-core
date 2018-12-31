<?php
/**
 * @author paulloft
 * @license MIT
 */

namespace Garden\Helpers;


class Arr {

    /**
     * Converts a quick array into a key/value form.
     *
     * @param array $array The array to work on.
     * @param mixed $default The default value for unspecified keys.
     * @return array Returns the array converted to long syntax.
     */
    public static function quick(array $array, $default): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $result[$value] = $default;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Converts a quick array into a key/value form using a callback to convert the short items.
     *
     * @param array $array The array to work on.
     * @param callable $callback The callback used to generate the default values.
     * @return array Returns the array converted to long syntax.
     */
    public static function uquick(array $array, callable $callback): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $result[$value] = $callback($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Сonverts an array into a string representation
     *
     * @param array $array
     * @return string
     */
    public static function export(array $array): string
    {
        $string = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $string = str_replace(['{', '}', '": '], ['[', ']', '" => '], $string);

        return $string;
    }

    /**
     * Load configuration data from a file into an array.
     *
     * @param string $path The path to load the file from.
     * @return array The configuration data.
     * @throws \InvalidArgumentException Throws an exception when the file type isn't supported.
     */
    public static function load($path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        // Get the extension of the file, but allow for .ini.php, .json.php etc.
        $ext = strstr(basename($path), '.');

        switch ($ext) {
            case '.json':
            case '.json.php':
                $loaded = json_decode(file_get_contents($path), true);
                break;
            case '.php':
                $loaded = include $path;
                break;
            case '.ser':
            case '.ser.php':
                $loaded = unserialize(file_get_contents($path), false);
                break;
            case '.yml':
            case '.yml.php':
                $loaded = yaml_parse_file($path);
                break;
            default:
                throw new \InvalidArgumentException("Invalid config extension $ext on $path.", 500);
        }
        return $loaded;
    }

    /**
     * Save an array of data to a specified path.
     *
     * @param array $data The data to save.
     * @param string $path The path to save to.
     * @param string $php_var The name of the php variable to load from if using the php file type.
     * @return bool Returns true if the save was successful or false otherwise.
     * @throws \InvalidArgumentException Throws an exception when the file type isn't supported.
     */
    public static function save($data, $path): bool
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Config::saveArray(): Argument #1 is not an array.', 500);
        }

        // Get the extension of the file, but allow for .ini.php, .json.php etc.
        $ext = strstr(basename($path), '.');

        switch ($ext) {
            case '.json':
            case '.json.php':
                if (defined('JSON_PRETTY_PRINT')) {
                    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                } else {
                    $json = json_encode($data);
                }
                $result = file_put_contents($path, $json, LOCK_EX);
                break;
            case '.php':
                $php = "<?php\nreturn " . self::export($data) . ';';
                $result = file_put_contents($path, $php, LOCK_EX);
                break;
            case '.ser':
            case '.ser.php':
                $ser = serialize($data);
                $result = file_put_contents($path, $ser, LOCK_EX);
                break;
            case '.yml':
            case '.yml.php':
                $yml = yaml_emit($data, YAML_UTF8_ENCODING, YAML_LN_BREAK);
                $result = file_put_contents($path, $yml, LOCK_EX);
                break;
            default:
                throw new \InvalidArgumentException("Invalid config extension $ext on $path.", 500);
        }
        return $result;
    }

    /**
     * Search an array for a value with a user-defined comparison function.
     *
     * @param mixed $needle The value to search for.
     * @param array $haystack The array to search.
     * @param callable $cmp The comparison function to use in the search.
     * @return mixed|false Returns the found value or false if the value is not found.
     */
    public static function usearch($needle, array $haystack, callable $cmp)
    {
        $found = array_uintersect($haystack, [$needle], $cmp);

        if (empty($found)) {
            return false;
        }

        return array_pop($found);
    }

    /**
     * Select the first non-empty value from an array.
     *
     * @param array $keys An array of keys to try.
     * @param array $array The array to select from.
     * @param mixed $default The default value if non of the keys exist.
     * @return mixed Returns the first non-empty value of {@link $default} if none are found.
     * @category Array Functions
     */
    public static function select(array $keys, array $array, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($array[$key]) && $array[$key]) {
                return $array[$key];
            }
        }
        return $default;
    }

    /**
     * Make sure that a key exists in an array.
     *
     * @param string|int $key The array key to ensure.
     * @param array &$array The array to modify.
     * @param mixed $default The default value to set if key does not exist.
     * @category Array Functions
     */
    public static function touch($key, array &$array, $default)
    {
        if (!array_key_exists($key, $array)) {
            $array[$key] = $default;
        }
    }

    /**
     * Take all of the items in an array and make a new array with them specified by mappings.
     *
     * @param array $array The input array to translate.
     * @param array $mappings The mappings to translate the array.
     * @return array
     *
     * @category Array Functions
     */
    public static function translate($array, array $mappings): array
    {
        $array = (array)$array;
        $result = [];
        foreach ($mappings as $index => $value) {
            if (is_numeric($index)) {
                $key = $value;
                $newKey = $value;
            } else {
                $key = $index;
                $newKey = $value;
            }
            $result[$newKey] = $array[$key] ?? null;
        }
        return $result;
    }

    /**
     * Like {@link implode()}, but joins array keys and values.
     *
     * @param string $elemglue The string that separates each element of the array.
     * @param string $keyglue The string that separates keys and values.
     * @param array $pieces The array of strings to implode.
     * @return string Returns the imploded array as a string.
     *
     * @category Array Functions
     * @category String Functions
     */
    public static function implodeAssoc($elemglue, $keyglue, array $pieces): array
    {
        $result = '';

        foreach ($pieces as $key => $value) {
            if ($result) {
                $result .= $elemglue;
            }

            $result .= $key . $keyglue . $value;
        }
        return $result;
    }

    /**
     * recursive analog array_map
     * @param callable $callbacks
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function mapRecursive(callable $callbacks, array $array, $keys = null): array
    {
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $array[$key] = self::mapRecursive($callbacks, $array[$key]);
            } elseif (!is_array($keys) || \in_array($key, $keys)) {
                if (is_array($callbacks)) {
                    foreach ($callbacks as $cb) {
                        $array[$key] = $cb($array[$key]);
                    }
                } else {
                    $array[$key] = $callbacks($array[$key]);
                }
            }
        }

        return $array;
    }

    /**
     * retrieves and returns value of the array by key
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function extract($key, array &$array, $default = null)
    {
        $result = $array[$key] ?? $default;
        unset($array[$key]);

        return $result;
    }

    /**
     * Safely get a value out of an array.
     * @param $array
     * @param $key
     * @param bool $dafult
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        return $array[$key] ?? $default;
    }

    /**
     * Return the value from an associative array.
     * This function differs from get() in that $key can be an array that will be used to walk a nested array.
     * @param string $path The keys or property names of the value. This can be an array or dot-seperated string.
     * @param array $array The array or object to search.
     * @param mixed $default The value to return if the key does not exist.
     * @param string $delimiter
     * @return mixed The value from the array.
     * @category Array Functions
     */
    public static function path(string $path, array $array, $default = null, string $delimiter = '.')
    {
        if (array_key_exists($path, $array)) {
            return $array[$path];
        }

        if (strpos($path, $delimiter) === false) {
            return $default;
        }

        foreach (explode($delimiter, $path) as $segment) {
            if (\is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Filter array with allowed keys
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function filterKeys(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * array_column from user function
     * @param \Iterator $array
     * @param callable $callback
     * @param string $key
     * @return array
     */
    public static function ucolumn(\Iterator $array, callable $callback, $key = false): array
    {
        $result = [];

        foreach ($array as $k => $v) {
            $result[$key ? $v[$key] : $k] = $callback($v, $k);
        }

        return $result;
    }


    /**
     * groups the array values by key
     * @param array $array
     * @param $groupKey
     * @return array
     */
    public static function group(array $array, $groupKey, $saveKeys = false): array
    {
        $result = [];
        foreach ($array as $key => $item) {
            if ($saveKeys) {
                $result[$item[$groupKey]][$key] = $item;
            } else {
                $result[$item[$groupKey]][] = $item;
            }
        }

        return $result;
    }

    /**
     * faster analog in_array function
     * @param $value
     * @param array $array
     * @return bool
     */
    public static function in($value, array $array): bool
    {
        $reverse = array_flip($array);
        return isset($reverse[$value]);
    }
}