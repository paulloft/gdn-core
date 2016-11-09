<?php

function smarty_function_javascript($Params, &$Smarty) {
    $js = valr('gdn.value.js', $Smarty->tpl_vars);

    if(empty($js)) return false;

    $html = '';
    $c = count($js);
    $i = 0;
    foreach ($js as $id=>$src) {
        $i++;
        $html .= '<script src="'.$src.'?v='.APP_VERSION.'" type="text/javascript" id="'.$id.'"></script>'.($i == $c ? null : "\n    ");
    }

    return $html;
}