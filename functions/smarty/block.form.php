<?php

function smarty_block_form($params, $content, Smarty_Internal_Template $template, &$repeat)
{
    if ($repeat) {
        return null;
    }

    $form = $template->getTemplateVars('gdn_form');

    if (!$form) {
        return '<div class="alert alert-danger">' . \Garden\Translate::get('Form class not initialized') . '</div>';
    }

    return $form->open($params) . $content . $form->close();
}