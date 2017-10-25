<?php
use Garden\Interfaces\Module as ModuleInterface;

function smarty_function_module($params, &$smarty) {
    $path = explode('/', array_extract('name', $params), 2);

    if (count($path) == 2) {
        list($addon, $name) = $path;
    } else {
        list($name) = $path;
        $addon = array_extract('addon', $params);
    }
    $name = ucfirst($name);
    $addon = ucfirst($addon);

    $id = array_extract('id', $params, null);
    /**
     * @var ModuleInterface $moduleName
     */
    $moduleName = "\\Addons\\$addon\\Modules\\$name";

    if(class_exists($moduleName)) {
        $module = $id ? $moduleName::instance($id) : $moduleName::instance();
        if (!$module instanceof ModuleInterface) {
            echo '<div class="alert alert-warning">'.t_sprintf('Module %s must implements %s', $name, ModuleInterface::class).'</div>';
        } else {
            try {
                echo $module->render($params);
            } catch (\Garden\Exception\NotFound $exception) {
                echo '<div class="alert alert-danger">Module <b>'.$moduleName.'</b>: '.$exception->getDescription().'</div>';
            }
        }
    } else {
        echo '<div class="alert alert-warning">'.t_sprintf('Module %s not found!', $name).'</div>';
    }
}