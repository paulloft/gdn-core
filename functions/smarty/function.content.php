<?php

function smarty_function_content($Params, Smarty_Internal_Template $template) {
    return \Garden\Helpers\Arr::get('content', $template->getTemplateVars('gdn'));
}