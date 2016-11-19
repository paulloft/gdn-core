<?php

function smarty_function_content($Params, &$Smarty) {
    return valr('gdn.value.content', $Smarty->tpl_vars, null);
}