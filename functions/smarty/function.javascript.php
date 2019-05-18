<?php

use Garden\Helpers\Arr;
use Garden\Helpers\Text;

function smarty_function_javascript($Params, Smarty_Internal_Template $template)
{
    $js = Arr::get($template->getTemplateVars('gdn'), 'js');

    if (empty($js)) {
        return false;
    }

    $html = '';
    $c = count($js);
    $i = 0;
    foreach ($js as $id => $src) {
        $i++;
        $static = Text::strBegins('static_', $id) ? ' data-static="true"' : '';
        $html .= '<script src="' . $src . '" type="text/javascript" id="' . $id . '"' . $static . '></script>' . ($i == $c ? null : "\n    ");
    }

    return $html;
}