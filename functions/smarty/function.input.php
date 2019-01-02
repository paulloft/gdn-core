<?php

function smarty_function_input($params, Smarty_Internal_Template $template)
{
    $form = $template->getTemplateVars('gdn_form');

    if (!$form) {
        return '<div class="alert alert-danger">' . \Garden\Translate::get('Form class not initialized') . '</div>';
    }

    $name = \Garden\Helpers\Arr::extract('name', $params);
    $type = \Garden\Helpers\Arr::extract('type', $params);

    return $form->input($name, $type, $params);
}