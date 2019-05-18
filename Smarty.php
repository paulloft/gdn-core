<?php

namespace Garden;

use Garden\Helpers\Date;
use Garden\Helpers\Text;

class Smarty extends \Smarty {

    public function __construct()
    {
        parent::__construct();

        $config = Config::get('smarty');
        $this->caching = $config['caching'] ?? false;

        $this
            ->setCompileDir($config['compile_dir'] ?? GDN_CACHE . '/smarty/')
            ->setCacheDir($config['cache_dir'] ?? GDN_CACHE . '/smarty/');

        $pluginDir = $config['plugins_dir'] ?? null;
        if ($pluginDir) {
            $this->addPluginsDir($pluginDir);
        }

        $this->registerClass('Date', Date::class);
        $this->registerClass('Text', Text::class);
        $this->registerPlugin('modifier', 'translate', [Translate::class, 'get']);
        $this->registerPlugin('modifier', 'config', [Config::class, 'get']);

        if (Cache::$clear) {
            $this->clearAllCache();
        }
    }
}