<?php
namespace Garden;

class Tasks extends Plugin {

    private $ticks = array(
        60 => 'minute',
        3600 => 'hour',
        86400 => 'day'
    );

    private $matches = array(
        'minutes' => 'i',
        'hours' => 'H',
        'day' => 'j',
        'month' => 'n'
    );

    private $lockfile;

    public function __construct()
    {
        $config = c('tasks');

        ini_set('memory_limit', val('memorylimit', $this->config, '256M'));
        $this->lockfile = val('lockfile', $this->config, GDN_CACHE."/.tasklock");
    }

    public function run()
    {
        if ($this->locked()) return false;

        $this->lock();

        $this->startMatches();

        $this->unlock();
    }

    public function startMatches()
    {
        $string = '';
        foreach ($this->matches as $name => $token) {
            $string = $string . '_' . date($token) . '_' . $name;
            $event = 'task_match' . $string;

//            if (Event::getHandlers($event)) {
                $this->log('Task: ' . $event);
                Event::fire($event);
//            }
        }
    }

    public function startTicks()
    {

    }

    public function locked()
    {
        if (file_exists($this->lockfile)) {
            $modifyTime = filemtime($this->lockfile);
            if (strtotime('55 minutes ago') > $modifyTime) {
                unlink($lockfile);
            } else {
                return true;
            }
        }

        return false;
    }

    public function lock()
    {
        touch($this->lockfile);
    }

    public function unlock()
    {
        unlink($this->lockfile);
    }

    public function log($string)
    {
        $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $string);
        print($line);
    }
}