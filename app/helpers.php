<?php

use Carbon\Carbon;

if (! function_exists('app_timezone')) {
    /**
     * Application display timezone (Pakistan).
     */
    function app_timezone(): string
    {
        return config('app.timezone', 'Asia/Karachi');
    }
}

if (! function_exists('format_datetime')) {
    /**
     * Format any date/time for display in Pakistani time.
     *
     * Usage:
     *   format_datetime(now())
     *   format_datetime($model->created_at)
     *   format_datetime($value, 'd M Y')
     */
    function format_datetime(mixed $datetime, string $format = 'd M Y, H:i'): string
    {
        if ($datetime === null || $datetime === '') {
            return '—';
        }

        return Carbon::parse($datetime)
            ->timezone(app_timezone())
            ->format($format);
    }
}

if (! function_exists('format_date')) {
    /**
     * Format date only — e.g. "Saturday, 29 August 2026"
     */
    function format_date(mixed $date, string $format = 'l, j F Y'): string
    {
        return format_datetime($date, $format);
    }
}
