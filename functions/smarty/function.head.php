<?php

function smarty_function_head($params, Smarty_Internal_Template $template) {
    $vars = $template->getTemplateVars('gdn');
    $title = $template->getTemplateVars('title');
    $meta  = \Garden\Helpers\Arr::get('meta', $vars);
    $sitename = c('main.sitename');
    $separator = c('main.titleSeparator', '-');
    
    $html = "<title>".strip_tags($title.' '.$separator.' '.$sitename)."</title>\n    ";

    if(!empty($meta)){
        $c = count($meta);
        $i = 0;
        foreach ($meta as $name => $value) {
            $i++;
            list($content, $http_equiv) = $value;
            $html .= '<meta '.($http_equiv ? 'http-equiv' : 'name').'="'.$name.'" content="'.$content.'" />'.($i == $c ? null : "\n    ");
        }
    }

    return $html;
}