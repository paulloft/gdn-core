<?php
return [
    'driver' => 'dirty',
    'file' => [
        'defaultLifetime' => 3600,
        'cacheDir' => 'cache',
    ],
    'memcache' => [
        'defaultLifetime' => 3600,
        'host' => 'localhost',
        'port' => '11211',
        'persistent' => false,
        'keyPrefix' => 'gdn_',
    ],
    'memcached' => [
        'defaultLifetime' => 3600,
        'host' => 'localhost',
        'port' => '11211',
        'persistent' => false,
        'keyPrefix' => 'gdn_',
    ],
    'redis' => [
        'defaultLifetime' => 3600,
        'host' => 'localhost',
        'port' => '6379',
        'keyPrefix' => 'gdn_',
        'timeout' => 0,
        'reserved' => null,
        'retry_interval' => 0,
    ],
];
