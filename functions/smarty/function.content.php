<?php

use Garden\Helpers\Arr;

function smarty_function_content($Params, Smarty_Internal_Template $template) {
    return Arr::get($template->getTemplateVars('gdn'), 'content');
}