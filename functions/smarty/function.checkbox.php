<?php

function smarty_function_checkbox($params, &$smarty) {
    $form = \Garden\Helpers\Arr::path('gdn_form.value', $smarty->tpl_vars);

    if (!$form) {
        return '<div class="alert alert-danger">'.t('Form class not initialized').'</div>';
    }

    $name = \Garden\Helpers\Arr::extract('name', $params);

    return $form->checkbox($name, $params);
}