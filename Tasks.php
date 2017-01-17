<?php
namespace Garden;

class Tasks extends Plugin {

    const ticks = [
        60    => 'minute',
        3600  => 'hour',
        86400 => 'day'
    ];

    const matches = [
        'minutes' => 'i',
        'hours'   => 'H',
        'dayname' => 'l',
        'day'     => 'j',
        'month'   => 'n'
    ];

    private $lockfile;

    public function __construct()
    {
        $config = c('tasks');

        set_time_limit(val('timelimit', $config, 900));
        ini_set('memory_limit', val('memorylimit', $config, '256M'));
        $this->lockfile = val('lockfile', $config, GDN_CACHE."/.tasklock");
    }

    public function run()
    {
        if ($this->locked()) return false;

        $this->lock();

        $this->startMatches();
        $this->startTicks();

        $this->unlock();
    }

    public function startMatches()
    {
        $string = '';
        foreach (self::matches as $name => $token) {
            if ($name == 'dayname') {
                $event = 'task_match' . $string . '_' . strtolower(date($token));
            } else {
                $string = $string . '_' . date($token) . '_' . $name;
                $event = 'task_match' . $string;
            }

            $this->log('Task: ' . $event);

            try {
                Event::fire($event);
            } catch (\Exception $exception) {

            }
        }
    }

    public function flatten($Array) {
        $Result = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveArrayIterator($Array)) as $Value)
            $Result[] = $Value;
        return $Result;
    }

    public function startTicks()
    {
        $range = array_merge(
            range(1, 99, 1),
            range(100, 999, 5)
        );

        $yearSeconds = (int)((time() - strtotime('1 Jan'))/60) * 60;

        foreach ($range as $i) {
            foreach (self::ticks as $second => $name) {
                $suffix = ($i == 1) ? '' : 's';
                if ($yearSeconds % $second == 0 && ($yearSeconds / $second) % $i == 0) {
                    $event = 'task_every_'.$i.'_'.$name.$suffix;
                    $this->log('Task: ' . $event);

                    try {
                        Event::fire($event);
                    } catch(\Exception $exception) {

                    }
                }
            }
        }
    }

    public function locked()
    {
        if (file_exists($this->lockfile)) {
            $modifyTime = filemtime($this->lockfile);
            if (strtotime('55 minutes ago') > $modifyTime) {
                unlink($this->lockfile);
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