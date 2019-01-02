<?php
/**
 * @author paulloft
 * @license MIT
 */

namespace Garden\Helpers;

use DateTimeZone;

class Date extends \DateTime {

    const FORMAT_SQL = 'd-m-Y H:i:s';
    const FORMAT_SQL_DATE = '%D-%M-%Y';
    const FORMAT_DATE = '%D.%M.%Y';
    const FORMAT_DATE_TIME = 'd.m.Y H:i';
    const FORMAT_DATE_TIME_SEC = 'd.m.Y H:i:s';
    const FORMAT_TIME = '%H:%I';
    const FORMAT_TIME_SEC = '%H:%I:%S';

    /**
     * @param string $date
     * @param null $timezone
     * @return $this
     * @throws \Exception
     */
    public static function create($date = 'now', $timezone = null): self
    {
        return new self($date, $timezone);
    }

    /**
     * Date constructor.
     * @param string $time
     * @param DateTimeZone|null $timezone
     * @throws \Exception
     */
    public function __construct(string $time = 'now', DateTimeZone $timezone = null)
    {
        parent::__construct($time, $timezone);
    }

    /**
     * To string conversion
     * @return string
     */
    public function __toString()
    {
        return $this->format(self::FORMAT_DATE_TIME_SEC);
    }

    /**
     * Add seconds to current datetime
     * @param int $seconds
     * @return Date
     */
    public function addSeconds(int $seconds): self
    {
        return $this->add(new \DateInterval("PT{$seconds}S"));
    }

    /**
     * @param string $intevalSpec
     * @return Date
     * @throws \Exception
     */
    public function addInterval(string $intevalSpec): self
    {
        return $this->add(new \DateInterval($intevalSpec));
    }

    /**
     * returns date in sql format
     * @param bool $time show time
     * @return string
     */
    public function toSql($time = true): string
    {
        return $this->format($time ? self::FORMAT_SQL : self::FORMAT_SQL_DATE);
    }

    /**
     * returns date string
     * @return string
     */
    public function toDate(): string
    {
        return $this->format(self::FORMAT_DATE);
    }

    /**
     * returns date and time
     * @param bool $seconds show seconds
     * @return string
     */
    public function toDateTime($seconds = false): string
    {
        return $this->format($seconds ? self::FORMAT_DATE_TIME_SEC : self::FORMAT_DATE_TIME);
    }

    /**
     * returns only time
     * @param bool $seconds show seconds
     * @return string
     */
    public function toTime($seconds = false): string
    {
        return $this->format($seconds ? self::FORMAT_TIME_SEC : self::FORMAT_TIME);
    }

    /**
     * Retruns full years after birthday
     * @param $birthday
     * @param bool $label
     * @return string
     * @throws \Exception
     */
    public function getAges(): string
    {
        return $this->diff(new \DateTime())->format('%y');
    }

    /**
     * Returns how much time has passed
     * @param $date
     * @return string
     */
    public function passed()
    {
        $diff = $this->diff(new \DateTime());

        if ($diff->format('%r')) { // future
            return $this->format(self::FORMAT_DATE_TIME);
        }

        switch ($diff) {
            case $diff->m:
                return $this->format(self::FORMAT_DATE_TIME);

            case ($diff->d > 0):
                return $diff->format('%d days ago');

            case ($diff->h > 0):
                return $diff->format('%h hours ago');

            case ($diff->i > 0):
                return $diff->format('%i minutes ago');

            case ($diff->s > 10):
                return 'less than a minute ago';

            default:
                return 'just now';
        }
    }
}