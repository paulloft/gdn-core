<?php

function smarty_function_textarea($params, Smarty_Internal_Template $template)
{
    $form = $template->getTemplateVars('gdn_form');

    if (!$form) {
        return '<div class="alert alert-danger">' . \Garden\Translate::get('Form class not initialized') . '</div>';
    }

    $name = \Garden\Helpers\Arr::extract('name', $params);

    return $form->textarea($name, $params);
}