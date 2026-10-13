<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class DateFilter
{
    public static function start(string $date): string
    {
        Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->validate();
        return Carbon::createFromFormat('Y-m-d', $date)->startOfDay()->toDateTimeString();
    }

    public static function nextDay(string $date): string
    {
        return Carbon::parse(self::start($date))->addDay()->toDateTimeString();
    }
}
