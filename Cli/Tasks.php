<?php

namespace Garden\Cli;

use Garden\Config;
use Garden\Event;
use Garden\Traits\Instance;

class Tasks {
    use Instance;

    const ticks = [
        60 => 'minute',
        3600 => 'hour',
        86400 => 'day'
    ];

    const matches = [
        'minutes' => 'i',
        'hours' => 'H',
        'dayname' => 'l',
        'day' => 'j',
        'month' => 'n'
    ];

    private $lockfile;

    public function __construct()
    {
        $config = Config::get('tasks');

        ini_set('memory_limit', $config['memorylimit'] ?? '256M');
        set_time_limit($config['timelimit'] ?? 15 * 60);
        $this->lockfile = $config['lockfile'] ?? GDN_CACHE . '/.tasklock';
    }

    /**
     * Run tasks
     */
    public function run()
    {
        if ($this->locked()) {
            return;
        }

        $this->lock();

        $this->startMatches();
        $this->startTicks();

        $this->unlock();
    }

    /**
     * Triggering Event time-bound
     */
    public function startMatches()
    {
        $string = '';
        foreach (self::matches as $name => $token) {
            if ($name === 'dayname') {
                $event = 'task_match' . $string . '_' . strtolower(date($token));
            } else {
                $string = $string . '_' . date($token) . '_' . $name;
                $event = 'task_match' . $string;
            }

            self::log('Task: ' . $event);
            self::fireEvent($event);
        }
    }

    /**
     * Triggering Event Interval
     */
    public function startTicks()
    {
        $range = array_merge(
            range(1, 99, 1),
            range(100, 999, 5)
        );

        $yearSeconds = (int)((time() - strtotime('1 Jan')) / 60) * 60;

        foreach ($range as $i) {
            foreach (self::ticks as $second => $name) {
                $suffix = ($i === 1) ? '' : 's';
                if ($yearSeconds % $second === 0 && ($yearSeconds / $second) % $i === 0) {
                    $event = 'task_every_' . $i . '_' . $name . $suffix;

                    self::log('Task: ' . $event);
                    self::fireEvent($event);
                }
            }
        }
    }

    /**
     * Checks lock tasks
     * @return bool
     */
    public function locked(): bool
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

    /**
     * Lock task
     */
    public function lock()
    {
        touch($this->lockfile);
    }

    /**
     * Unlock tasks
     */
    public function unlock()
    {
        unlink($this->lockfile);
    }

    /**
     * Output in console
     * @param $string
     */
    public static function log($string)
    {
        $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $string);
        print($line);
    }

    /**
     * @param $event
     */
    protected static function fireEvent($event)
    {
        $handlers = Event::getHandlers($event);

        foreach ($handlers as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $callback();
                } catch (\Exception $e) {
                    self::log($e);
                }
            }
        }
    }
}