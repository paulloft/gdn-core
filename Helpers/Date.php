<?php
/**
 * @author paulloft
 * @license MIT
 */

namespace Garden\Helpers;


class Date {
    /**
     * @param int|string $date
     * @param string $modifier
     * @return string
     */
    public static function sql($date = null, $modifier = null): string
    {
        if ($date === null) {
            $date = time();
        } elseif (is_string($date)) {
            $date = (int)strtotime($date);
        }

        if ($modifier) {
            $date = (int)strtotime($modifier, $date);
        }

        return date('Y-m-d H:i:s', $date);
    }

    /**
     * @param int|string $date
     * @param string $format
     * @param string $modifier
     * @return string
     */
    public static function convert($date, $format = 'indatetime', $modifier = null): string
    {
        if (empty($date)) {
            return false;
        }

        $month = ['Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря'];

        if (is_string($date)) {
            $date = (int)strtotime($date);
        }

        if ($modifier) {
            $date = (int)strtotime($modifier, $date);
        }

        switch ($format) {
            case ('fullDate')       :
                $date = date('j', $date) . ' ' . $month[date('m', $date) - 1] . ' ' . date('Y', $date);
                break;
            case ('fullDateTime')   :
                $date = date('j', $date) . ' ' . $month[date('m', $date) - 1] . ' ' . date('Y в H:i', $date);
                break;
            case ('shortDateTime')  :
                $date = date('j', $date) . ' ' . $month[date('m', $date) - 1] . ' ' . date(' в H:i', $date);
                break;
            case ('date')           :
                $date = date('d.m.Y', $date);
                break;
            case ('time')           :
                $date = date('H:i', $date);
                break;
            case ('timesec')           :
                $date = date('H:i:s', $date);
                break;
            case ('datetime')       :
                $date = date('d.m.Y H:i', $date);
                break;
            case ('datefulltime')   :
                $date = date('d.m.Y H:i:s', $date);
                break;

            case ('indatetime')     :
                $date = date('d.m.Y в H:i', $date);
                break;
            case ('indatefulltime') :
                $date = date('d.m.Y в H:i:s', $date);
                break;

            case ('sql')            :
                $date = date('Y-m-d H:i:s', $date);
                break;
            default                 :
                $date = date($format, $date);
                break;
        }

        return $date;
    }

    /**
     * Compare two dates formatted as either timestamps or strings.
     *
     * @param mixed $date1 The first date to compare expressed as an integer timestamp or a string date.
     * @param mixed $date2 The second date to compare expressed as an integer timestamp or a string date.
     * @return int Returns `1` if {@link $date1} > {@link $date2}, `-1` if {@link $date1} > {@link $date2},
     * or `0` if the two dates are equal.
     * @category Date/Time Functions
     */
    public static function compare($date1, $date2 = false): int
    {
        if (!$date2) {
            $date2 = time();
        }

        if (is_string($date1)) {
            $date1 = strtotime($date1);
        }

        if (is_string($date2)) {
            $date2 = strtotime($date2);
        }

        if ($date1 > $date2) {
            return 1;
        }

        if ($date1 < $date2) {
            return -1;
        }

        return 0; // $date1 === $date2
    }

    /**
     * Retruns full years after birthday
     * @param $birthday
     * @param bool $label
     * @return string
     * @throws \Exception
     */
    public static function getAges($birthday): string
    {
        $now = new \DateTime();
        $age = $now->diff(new \DateTime($birthday));

        return $age->format('%y');
    }

    /**
     * Returns how much time has passed
     * @param $date
     * @return string
     */
    public static function passed($date)
    {
        if (is_string($date)) {
            $date = strtotime($date);
        }

        $diff = time() - $date;
        switch ($diff) {
            case ($diff < 10):
                return 'только что';
                break;
            case ($diff < 60):
                return 'менее минуты';
                break;

            case ($diff < 3600):
                $tm = ceil($diff / 60);
                $forms = ['минут', 'минута', 'минуты'];
                return $tm . ' ' . Text::declension($tm, $forms) . ' назад';
                break;

            case ($diff < 86400):
                $tm = ceil($diff / 3600);
                $forms = ['часов', 'час', 'часа'];
                return $tm . ' ' . Text::declension($tm, $forms) . ' назад';
                break;

            case ($diff < 604800):
                $tm = round($diff / 86400);
                $forms = ['дней', 'день', 'дня'];
                return $tm . ' ' . Text::declension($tm, $forms) . ' назад';
                break;

            default:
                return self::convert($date);
                break;
        }
    }
}