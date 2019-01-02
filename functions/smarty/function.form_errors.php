<?php

function smarty_function_form_errors($params, Smarty_Internal_Template $template)
{
    $form = $template->getTemplateVars('gdn_form');

    if (!$form) {
        return '<div class="alert alert-danger">' . \Garden\Translate::get('Form class not initialized') . '</div>';
    }

    $text = val('text', $params);
    $errors = $form->errors($text);

    return empty($errors) ? null : '<div class="form-errors">' . $errors . '</div>';
}