<?php

namespace App\Support;

class Format
{
    public static function money(float|int|string|null $amount): string
    {
        return config('membership.currency', '₱').number_format((float) $amount, 2);
    }
}
