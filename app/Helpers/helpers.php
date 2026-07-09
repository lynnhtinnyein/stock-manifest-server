<?php

if (!function_exists('formatDate')) {
    function formatDate(?string $date): string
    {
        return !empty($date)
            ? \Carbon\Carbon::parse($date)->format('Y/m/d')
            : 'YYYY/MM/DD';
    }
}

if (!function_exists('formatDateLong')) {
    function formatDateLong(?string $date): string
    {
        return !empty($date)
            ? \Carbon\Carbon::parse($date)->format('j F')
            : 'N/A';
    }
}
