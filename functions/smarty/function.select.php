<?php

function smarty_function_select($params, &$smarty) {
    $form = \Garden\Helpers\Arr::path('gdn_form.value', $smarty->tpl_vars);

    if (!$form) {
        return '<div class="alert alert-danger">'.t('Form class not initialized').'</div>';
    }

    $name    = \Garden\Helpers\Arr::extract('name', $params);
    $options = \Garden\Helpers\Arr::extract('options', $params);

    return $form->select($name, $options, $params);
}