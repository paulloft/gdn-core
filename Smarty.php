<?php

namespace Garden;

class Smarty extends \Smarty {

    public function __construct()
    {
        parent::__construct();

        $config = c('smarty');
        $this->caching = val('caching', $config);
        $this
            ->setCompileDir( val('compile_dir', $config, GDN_CACHE.'/smarty/') )
            ->setCacheDir( val('cache_dir', $config, GDN_CACHE.'/smarty/') )
            ->setPluginsDir( val('plugins_dir', $config) );

        if (Cache::$clear) {
            $this->clearAllCache();
        }
    }
}