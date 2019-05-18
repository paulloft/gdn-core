<?php

use Garden\Config;
use Garden\Helpers\Arr;

function smarty_function_head($params, Smarty_Internal_Template $template)
{
    $vars = $template->getTemplateVars('gdn');
    $title = $template->getTemplateVars('title');
    $meta = Arr::get($vars, 'meta');
    $sitename = Config::get('main.sitename');
    $separator = Config::get('main.titleSeparator', '-');

    $html = '<title>' . strip_tags($title . ' ' . $separator . ' ' . $sitename) . "</title>\n    ";

    if (!empty($meta)) {
        $count = count($meta);
        $i = 0;
        foreach ($meta as $name => list($content, $http_equiv)) {
            $i++;
            $html .= '<meta ' . ($http_equiv ? 'http-equiv' : 'name') . '="' . $name . '" content="' . $content . '" />' . ($i === $count ? null : "\n    ");
        }
    }

    return $html;
}