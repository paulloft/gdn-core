<?php

function smarty_function_checkbox($params, Smarty_Internal_Template $template)
{
    $form = $template->getTemplateVars('gdn_form');

    if (!$form) {
        return '<div class="alert alert-danger">' . \Garden\Translate::get('Form class not initialized') . '</div>';
    }

    $name = \Garden\Helpers\Arr::extract($params, 'name');

    return $form->checkbox($name, $params);
}