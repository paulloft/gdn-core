<?php

function smarty_function_input($params, Smarty_Internal_Template $template)
{
    $form = $template->getTemplateVars('gdn_form');

    if (!$form) {
        return '<div class="alert alert-danger">' . \Garden\Translate::get('Form class not initialized') . '</div>';
    }

    $name = \Garden\Helpers\Arr::extract($params, 'name');
    $type = \Garden\Helpers\Arr::extract($params, 'type');

    return $form->input($name, $type, $params);
}