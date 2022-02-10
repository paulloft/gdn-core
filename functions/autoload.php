<?php
define('APP_VERSION', '1.1');
define('GDN_SRC', dirname(__DIR__));


if (!function_exists('p')) {
    function p(...$args) {
        foreach ($args as $a) {
            \Utils\Dumphper::dump($a);
        }
    }
}

if (!function_exists('d')) {
    function d(...$args) {
        p(...$args);
        exit();
    }
}
