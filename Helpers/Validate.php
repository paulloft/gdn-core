<?php
/**
 * @author paulloft
 * @license MIT
 */

namespace Garden\Helpers;

use Garden\Model;
use function in_array;
use function is_int;

class Validate
{
    public const RULE_EMAIL = 'email';
    public const RULE_IP = 'ip';
    public const RULE_URL = 'url';
    public const RULE_MAC = 'mac';
    public const RULE_IS_EMPTY = 'isEmpty';
    public const RULE_REQUIRED = 'required';
    public const RULE_NOT_IN = 'notIn';
    public const RULE_IN = 'in';
    public const RULE_MIN_LENGTH = 'minLength';
    public const RULE_MAX_LENGTH = 'maxLength';
    public const RULE_LENGTH = 'length';
    public const RULE_INT = 'int';
    public const RULE_NUMERIC = 'numeric';
    public const RULE_MIN_VALUE = 'minValue';
    public const RULE_MAX_VALUE = 'maxValue';
    public const RULE_REGEXP = 'regexp';
    public const RULE_DATESQL = 'dateSql';
    public const RULE_MIN_DATE = 'minDate';
    public const RULE_MAX_DATE = 'maxDate';
    public const RULE_UNIQUE = 'unique';

    public static $emptyValues = [null, false, '', []];

    /**
     * is email string
     * @param $value
     * @return bool
     */
    public static function email($value): bool
    {
        return !(filter_var($value, FILTER_VALIDATE_EMAIL) === false);
    }

    /**
     * is ip string
     * @param $value
     * @return bool
     */
    public static function ip($value): bool
    {
        return !(filter_var($value, FILTER_VALIDATE_IP) === false);
    }

    /**
     * is url
     * @param $value
     * @return bool
     */
    public static function url($value): bool
    {
        return !(filter_var($value, FILTER_VALIDATE_URL) === false);
    }

    /**
     * is local url
     * @param $value
     * @return bool
     */
    public static function localUrl($value): bool
    {
        return !preg_match('#^(http|//)#', $value);
    }

    /**
     * is mac
     * @param $value
     * @return bool
     */
    public static function mac($value): bool
    {
        return !(filter_var($value, FILTER_VALIDATE_MAC) === false);
    }

    public static function isEmpty($value): bool
    {
        return in_array($value, self::$emptyValues, true);
    }

    /**
     * is not empty
     * @param $value
     * @return bool
     */
    public static function notEmpty($value): bool
    {
        return !in_array($value, self::$emptyValues, true);
    }

    /**
     * alias function notEmpty
     * @param $value
     * @return bool
     */
    public static function required($value): bool
    {
        return !in_array($value, self::$emptyValues, true);
    }

    /**
     * check value not in array
     * @param $value
     * @param $params
     * @return bool
     */
    public static function notIn($value, array $params): bool
    {
        return !in_array($value, $params);
    }

    /**
     * check value not in array
     * @param $value
     * @param $params
     * @return bool
     */
    public static function in($value, array $params): bool
    {
        return in_array($value, $params);
    }

    /**
     * check min lenght string
     * @param $value
     * @param $length
     * @return bool
     */
    public static function minLength($value, $length): bool
    {
        return (mb_strlen($value) >= $length);
    }

    /**
     * check min lenght string
     * @param $value
     * @param $lenght
     * @return bool
     */
    public static function maxLength($value, $lenght): bool
    {
        return (mb_strlen($value) <= $lenght);
    }

    /**
     * check lenght string
     * @param $value
     * @param $lenght
     * @return bool
     */
    public static function length($value, $lenght): bool
    {
        return mb_strlen($value) === $lenght;
    }

    /**
     * @param $value
     * @param $params
     * @return bool
     */
    public static function int($value): bool
    {
        return is_int($value);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function numeric($value): bool
    {
        return is_numeric($value);
    }

    /**
     * @param $value
     * @param $minValue
     * @return bool
     */
    public static function minValue($value, $minValue): bool
    {
        return (int)$value >= $minValue;
    }

    /**
     * @param $value
     * @param $maxValue
     * @return bool
     */
    public static function maxValue($value, $maxValue): bool
    {
        return (int)$value <= $maxValue;
    }

    /**
     * @param $value
     * @param $regexp
     * @return bool
     */
    public static function regexp($value, $regexp): bool
    {
        return preg_match($regexp, (string)$value);
    }

    /**
     * check string is sql date format
     * @param $value
     * @return bool
     */
    public static function dateSql($value): bool
    {
        $date = '/^(\\d{4})-(\\d{2})-(\\d{2})$/';
        $datetime = '/^(\\d{4})-(\\d{2})-(\\d{2}) ([01]\\d|2[0-3]):([0-5]\\d):([0-5]\\d)$/';

        return (preg_match($date, $value, $matches) && checkdate($matches[2], $matches[3], $matches[1]))
            ||
            (preg_match($datetime, $value, $matches) && checkdate($matches[2], $matches[3], $matches[1]));
    }

    /**
     * @param $value
     * @param $minDate
     * @return bool
     */
    public static function minDate($value, $minDate): bool
    {
        return strtotime($value) >= (is_int($minDate) ? $minDate : strtotime($minDate));
    }

    /**
     * @param $value
     * @param $maxDate
     * @return bool
     */
    public static function maxDate($value, $maxDate): bool
    {
        return strtotime($value) <= (is_int($maxDate) ? $maxDate : strtotime($maxDate));
    }

    /**
     * check field unique value
     * @param $value
     * @param array $params
     * @return bool
     */
    public static function unique($value, array $params): bool
    {
        /**
         * @var $model Model
         */
        [$id, $field, $model] = $params;
        $count = $model->getCount([
            $field => $value,
            $model->getPrimaryKey() . '!=' => $id
        ]);

        return !$count;
    }

}