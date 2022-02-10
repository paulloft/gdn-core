<?php
/**
 * @author paulloft
 * @license MIT
 */

namespace Garden\Helpers;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

class Date extends DateTime
{
    public const FORMAT_SQL = 'Y-m-d H:i:s';
    public const FORMAT_SQL_DATE = 'Y-m-d';
    public const FORMAT_DATE = 'd.m.Y';
    public const FORMAT_DATE_TIME = 'd.m.Y H:i';
    public const FORMAT_DATE_TIME_SEC = 'd.m.Y H:i:s';
    public const FORMAT_TIME = 'H:i';
    public const FORMAT_TIME_SEC = 'H:i:s';

    /**
     * Date constructor.
     * @param string $time
     * @param DateTimeZone|null $timezone
     */
    public function __construct(string $time = 'now', DateTimeZone $timezone = null)
    {
        parent::__construct($time, $timezone);
    }

    /**
     * Create new Datetime object from string
     * @param string|DateTime $date
     * @param DateTimeZone $timezone
     * @return $this
     */
    public static function create(string $date = 'now', DateTimeZone $timezone = null): self
    {
        return new self($date, $timezone);
    }

    /**
     * Create new Datetime object from timestamp
     * @param int $timestamp
     * @param DateTimeZone $timezone
     * @return Date
     */
    public static function createTimestamp(int $timestamp, DateTimeZone $timezone = null): self
    {
        $date = new self('now', $timezone);
        return $date->setTimestamp($timestamp);
    }

    /**
     * Create new Datetime object from Datetime
     * @param DateTime $dateTime
     * @return static
     */
    public static function createDateTime(DateTime $dateTime): self
    {
        return (new self())->setTimestamp($dateTime->getTimestamp());
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
        return $this->addInterval("PT{$seconds}S");
    }

    /**
     * @param string $intevalSpec
     * @return Date
     * @throws Exception
     */
    public function addInterval(string $intevalSpec): self
    {
        return $this->add(new DateInterval($intevalSpec));
    }

    /**
     * Add minutes to current datetime
     * @param int $minutes
     * @return Date
     */
    public function addMinutes(int $minutes): self
    {
        return $this->addInterval("PT{$minutes}M");
    }

    /**
     * Add hours to current datetime
     * @param int $hours
     * @return Date
     */
    public function addHours(int $hours): self
    {
        return $this->addInterval("PT{$hours}H");
    }

    /**
     * Add weeks to current datetime
     * @param int $weeks
     * @return Date
     */
    public function addWeeks(int $weeks): self
    {
        return $this->addInterval("P{$weeks}W");
    }

    /**
     * Add days to current datetime
     * @param int $days
     * @return Date
     */
    public function addDays(int $days): self
    {
        return $this->addInterval("P{$days}D");
    }

    /**
     * Add months to current datetime
     * @param int $months
     * @return Date
     */
    public function addMonths(int $months): self
    {
        return $this->addInterval("P{$months}M");
    }

    /**
     * Add years to current datetime
     * @param int $years
     * @return Date
     */
    public function addYears(int $years): self
    {
        return $this->addInterval("P{$years}Y");
    }

    /**
     * Subtracts seconds to current datetime
     * @param int $seconds
     * @return Date
     */
    public function subSeconds(int $seconds): self
    {
        return $this->subInterval("PT{$seconds}S");
    }

    /**
     * @param string $intevalSpec
     * @return Date
     * @throws Exception
     */
    public function subInterval(string $intevalSpec): self
    {
        return $this->sub(new DateInterval($intevalSpec));
    }

    /**
     * Subtracts minutes to current datetime
     * @param int $minutes
     * @return Date
     */
    public function subMinutes(int $minutes): self
    {
        return $this->subInterval("PT{$minutes}M");
    }

    /**
     * Subtracts hours to current datetime
     * @param int $hours
     * @return Date
     */
    public function subHours(int $hours): self
    {
        return $this->subInterval("PT{$hours}H");
    }

    /**
     * Subtracts weeks to current datetime
     * @param int $weeks
     * @return Date
     */
    public function subWeeks(int $weeks): self
    {
        return $this->subInterval("P{$weeks}W");
    }

    /**
     * Subtracts days to current datetime
     * @param int $days
     * @return Date
     */
    public function subDays(int $days): self
    {
        return $this->subInterval("P{$days}D");
    }

    /**
     * Subtracts months to current datetime
     * @param int $months
     * @return Date
     */
    public function subMonths(int $months): self
    {
        return $this->subInterval("P{$months}M");
    }

    /**
     * sub Subtracts to current datetime
     * @param int $years
     * @return Date
     */
    public function subYears(int $years): self
    {
        return $this->subInterval("P{$years}Y");
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
     * @return string
     * @throws Exception
     */
    public function getAges(): string
    {
        return $this->diff(new DateTime())->format('%y');
    }

    /**
     * Returns how much time has passed
     * @return string
     */
    public function passed(): ?string
    {
        $diff = $this->diff(new DateTime());

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
