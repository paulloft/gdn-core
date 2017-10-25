<?php
namespace Garden\Interfaces;

interface Module
{
    /**
     * instance function
     * @param array ...$args
     * @return mixed
     */
    public static function instance(...$args);

    /**
     * Rendering function
     * @param array $params
     * @return string
     */
    public function render(array $params = []);
}