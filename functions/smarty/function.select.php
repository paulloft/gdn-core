<?php

function smarty_function_select($params, Smarty_Internal_Template $template)
{
    $form = $template->getTemplateVars('gdn_form');

    if (!$form) {
        return '<div class="alert alert-danger">' . \Garden\Translate::get('Form class not initialized') . '</div>';
    }

    $name = \Garden\Helpers\Arr::extract('name', $params);
    $options = \Garden\Helpers\Arr::extract('options', $params);

    return $form->select($name, $options, $params);
}