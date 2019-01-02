<?php

function smarty_function_javascript($Params, Smarty_Internal_Template $template)
{
    $js = \Garden\Helpers\Arr::get('js', $template->getTemplateVars('gdn'));

    if (empty($js)) {
        return false;
    }

    $html = '';
    $c = count($js);
    $i = 0;
    foreach ($js as $id => $src) {
        $i++;
        $static = \Garden\Helpers\Text::strBegins('static_', $id) ? ' data-static="true"' : '';
        $html .= '<script src="' . $src . '" type="text/javascript" id="' . $id . '"' . $static . '></script>' . ($i == $c ? null : "\n    ");
    }

    return $html;
}