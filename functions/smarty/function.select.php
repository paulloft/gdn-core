<?php

use Garden\Helpers\Arr;
use Garden\Translate;

function smarty_function_select($params, Smarty_Internal_Template $template)
{
    $form = $template->getTemplateVars('gdn_form');

    if (!$form) {
        return '<div class="alert alert-danger">' . Translate::get('Form class not initialized') . '</div>';
    }

    $name = Arr::extract($params, 'name');
    $options = Arr::extract($params, 'options');

    return $form->select($name, $options, $params);
}