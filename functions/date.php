<?php
/**
 * @param int|string $date
 * @param string $modifier
 * @return string
 */
function date_sql($date = null, $modifier = null)
{
    if ($date === null) {
        $date = time();
    } elseif (is_string($date)) {
        $date = strtotime($date);
    }

    if ($modifier) {
        $date = strtotime($modifier, $date);
    }

    return date('Y-m-d H:i:s', $date);
}

/**
 * @param int|string $date
 * @param string $format
 * @param string $modifier
 * @return string
 */
function date_convert($date, $format = 'indatetime', $modifier = null)
{
    if (empty($date)) {
        return false;
    }

    $month = ['Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря'];

    if (is_string($date)) {
        $date = strtotime($date);
    }

    if ($modifier) {
        $date = strtotime($modifier, $date);
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
function date_compare($date1, $date2 = false)
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
    if ($date1 == $date2) {
        return 0;
    }
    if ($date1 < $date2) {
        return -1;
    }
}

function date_get_ages($birthday, $label = true)
{
    $now = new \DateTime('now');
    $age = $now->diff(new \DateTime($birthday));
    $ages = $age->format('%y');

    return $label ? $ages . ' ' . format_declension($ages, ['лет', 'год', 'года']) : $ages;
}

function date_passed($date)
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
            return $tm . ' ' . format_declension($tm, $forms) . ' назад';
            break;

        case ($diff < 86400):
            $tm = ceil($diff / 3600);
            $forms = ['часов', 'час', 'часа'];
            return $tm . ' ' . format_declension($tm, $forms) . ' назад';
            break;

        case ($diff < 604800):
            $tm = round($diff / 86400);
            $forms = ['дней', 'день', 'дня'];
            return $tm . ' ' . format_declension($tm, $forms) . ' назад';
            break;

        default:
            return date_convert($date);
            break;
    }
}

/**
 * Вычислить разницу между двумя датами
 * @param string $start
 * @param string $end
 * @param string $differenceFormat Формат даты, возвращаемый функцией
 * @return string
 */
function date_difference($start, $end, $differenceFormat = '%a')
{
    $datetime1 = date_create($start);
    $datetime2 = date_create($end);

    $interval = date_diff($datetime1, $datetime2);

    return $interval->format($differenceFormat);
}
