<?php

function smarty_function_form_errors($params, &$smarty) {
    $form = valr('gdn_form.value', $smarty->tpl_vars);

    if (!$form) {
        return '<div class="alert alert-danger">'.t('Form class not initialized').'</div>';
    }

    $text = val('text', $params);
    $errors = $form->errors($text);

    return empty($errors) ? null : '<div class="form-errors">'.$errors.'</div>';
}