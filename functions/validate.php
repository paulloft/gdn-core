<?php
function validate_email($value, $params)
{
    return !(filter_var($value, FILTER_VALIDATE_EMAIL) === false);
}

function validate_ip($value, $params)
{
    return !(filter_var($value, FILTER_VALIDATE_IP) === false) ;
}

function validate_url($value, $params)
{
    return !(filter_var($value, FILTER_VALIDATE_URL) === false);
}

function validate_mac($value, $params)
{
    return !(filter_var($value, FILTER_VALIDATE_MAC) === false);
}

function validate_not_empty($value, $params)
{
    return !in_array($value, array(NULL, FALSE, '', array()), TRUE);
}

function validate_not_value($value, array $params)
{
    return !in_array($value, $params);
}

function validate_min_length($value, $params)
{
    return (mb_strlen($value) >= $params);
}

function validate_max_length($value, $params)
{
    return (mb_strlen($value) <= $params);
}

function validate_length($value, $params)
{
    return mb_strlen($value) === $params;
}

function validate_int($value, $params)
{
    return is_int($value);
}

function validate_numeric($value, $params)
{
    return is_numeric($value);
}

function validate_regexp($value, $params)
{
    return preg_match($params, (string)$value);
}

function validate_sql_date($value)
{
    $date = "/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/";
    $datetime = "/^(\\d{4})-(\\d{2})-(\\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/";

    return preg_match($date, $value, $matches)
        && checkdate($matches[2], $matches[3], $matches[1])
        || preg_match($datetime, $value, $matches)
        && checkdate($matches[2], $matches[3], $matches[1]);
}
