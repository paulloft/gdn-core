<?php
/**
* Dumps information about arguments passed to functions
*
*/

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