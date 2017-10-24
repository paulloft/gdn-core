<?php

function smarty_function_javascript($Params, &$Smarty)
{
    $js = valr('gdn.value.js', $Smarty->tpl_vars, []);

    if (empty($js)) {
        return false;
    }

    $html = '';
    $c = count($js);
    $i = 0;
    foreach ($js as $id => $src) {
        $i++;
        $static = str_begins('static_', $id) ? ' data-static="true"' : '';
        $html .= '<script src="' . $src . '" type="text/javascript" id="' . $id . '"' . $static . '></script>' . ($i == $c ? null : "\n    ");
    }

    return $html;
}