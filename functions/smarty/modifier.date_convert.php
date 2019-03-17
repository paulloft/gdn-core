<?php

function smarty_modifier_date_convert($dateString, $format = 'date_time')
{
    if (empty($dateString)) {
        return '';
    }

    switch ($format) {
        case 'sql':
            $format = \Garden\Helpers\Date::FORMAT_SQL;
            break;

        case 'sql_date':
            $format = \Garden\Helpers\Date::FORMAT_SQL_DATE;
            break;
        case 'date':
            $format = \Garden\Helpers\Date::FORMAT_DATE;
            break;

        case 'date_time':
            $format = \Garden\Helpers\Date::FORMAT_DATE_TIME;
            break;

        case 'date_time_sec':
            $format = \Garden\Helpers\Date::FORMAT_DATE_TIME_SEC;
            break;

        case 'time':
            $format = \Garden\Helpers\Date::FORMAT_TIME;
            break;

        case 'time_sec':
            $format = \Garden\Helpers\Date::FORMAT_TIME_SEC;
            break;
    }

    return \Garden\Helpers\Date::create($dateString)->format($format);
}