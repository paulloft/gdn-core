<?php

namespace Garden;

use Garden\Helpers\Date;
use Garden\Helpers\Text;

class Smarty extends \Smarty {

    public function __construct()
    {
        parent::__construct();

        $config = Config::get('smarty');
        $this->caching = val('caching', $config);
        $this
            ->setCompileDir(val('compile_dir', $config, GDN_CACHE . '/smarty/'))
            ->setCacheDir(val('cache_dir', $config, GDN_CACHE . '/smarty/'))
            ->setPluginsDir(val('plugins_dir', $config));

        $this->registerClass('Date', Date::class);
        $this->registerClass('Text', Text::class);
        $this->registerPlugin('modifier', 'translate', [Translate::class, 'get']);
        $this->registerPlugin('modifier', 'config', [Config::class, 'get']);

        if (Cache::$clear) {
            $this->clearAllCache();
        }
    }
}