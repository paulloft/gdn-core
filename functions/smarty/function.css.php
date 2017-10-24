<?php

function smarty_function_css($Params, &$Smarty)
{
    $css = valr('gdn.value.css', $Smarty->tpl_vars);

    if(empty($css)) {
        return false;
    }

    $html = '';
    $c = count($css);
    $i = 0;
    foreach ($css as $id=>$src) {
        $i++;
        $html .= '<link href="'.$src.'" rel="stylesheet" type="text/css" id="'.$id.'" />'.($i == $c ? null : "\n    ");
    }

    return $html;
}