<?php

use Garden\Interfaces\Module as ModuleInterface;

function smarty_function_module($params)
{
    $path = explode('/', \Garden\Helpers\Arr::extract('name', $params), 2);

    if (\count($path) === 2) {
        list($addon, $name) = $path;
    } else {
        list($name) = $path;
        $addon = \Garden\Helpers\Arr::extract('addon', $params);
    }
    $name = ucfirst($name);
    $addon = ucfirst($addon);

    $id = \Garden\Helpers\Arr::extract('id', $params);
    /**
     * @var ModuleInterface $moduleName
     */
    $moduleName = "\\Addons\\$addon\\Modules\\$name";

    if (class_exists($moduleName)) {
        $module = $id ? $moduleName::instance($id) : $moduleName::instance();
        if (!$module instanceof ModuleInterface) {
            echo '<div class="alert alert-warning">' . \Garden\Translate::getSprintf('Module %s must implements %s', $name, ModuleInterface::class) . '</div>';
        } else {
            try {
                echo $module->render($params);
            } catch (\Garden\Exception\NotFound $exception) {
                echo '<div class="alert alert-danger">Module <b>' . $moduleName . '</b>: ' . $exception->getDescription() . '</div>';
            }
        }
    } else {
        echo '<div class="alert alert-warning">' . \Garden\Translate::getSprintf('Module %s not found!', $name) . '</div>';
    }
}