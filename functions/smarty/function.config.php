<?php

// Translate function
function smarty_function_config($Params) {
    $code = val('code', $Params);
    $default = val('default', $Params);
    
    return \Garden\Config::get($code, $default);
}