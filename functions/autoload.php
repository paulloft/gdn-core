<?php
define('APP_VERSION', '1.1');
define('GDN_SRC', dirname(__DIR__));


// Include the core functions.
require_once __DIR__.'/core-functions.php';
// Load the framework's overridable functions late so that addons can override them.
require_once __DIR__.'/debug.php';
require_once __DIR__.'/pluggable.php';
require_once __DIR__.'/formatting.php';
require_once __DIR__.'/date.php';
require_once __DIR__.'/array.php';
require_once __DIR__.'/validate.php';