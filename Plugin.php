<?php

namespace Garden;

abstract class Plugin {
    /**
     * Returns the application singleton or null if the singleton has not been created yet.
     * @return $this
     */
    public static function instance() {
        $class_name = get_called_class();
        $args = func_get_args();
        
        return Factory::getArray($class_name, $args);
    }
}

