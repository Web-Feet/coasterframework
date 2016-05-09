<?php namespace CoasterCms\Helpers;

use Carbon\Carbon;

class DateTimeHelper
{

    public static function display($dateTime, $format = 'long')
    {
        if (is_string($dateTime) || is_int($dateTime)) {
            $dateTime = new Carbon($dateTime);
        }
        return $dateTime->format(config('coaster::date.format.'.$format));
    }

}