<?php

function smarty_function_module($Params, &$Smarty) {
    $name = array_extract('name', $Params);
    $id   = array_extract('id', $Params, null);
    $moduleName = '\\'.ucfirst($name).'Module';

    if(class_exists($moduleName)) {
        $module = $id ? $moduleName::instance($id) : $moduleName::instance();
        try {
            echo $module->render($Params);
        } catch (\Garden\Exception\NotFound $exception) {
            echo '<div class="alert alert-danger">Module <b>'.$moduleName.'</b>: '.$exception->getDescription().'</div>';
        }
    } else {
        echo '<div class="alert alert-warning">'.t_sprintf('Module %s not found!', $name).'</div>';
    }
}