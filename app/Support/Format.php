<?php

namespace App\Support;

class Format
{
    /**
     * Rupiah ringkas untuk kartu dan "nota jahit".
     * ID: Rp25rb, Rp150rb, Rp1,2jt | EN: Rp25k, Rp150k, Rp1.2m
     */
    public static function rupiahShort(int $amount): string
    {
        $isId = app()->getLocale() === 'id';

        if ($amount >= 1_000_000) {
            $value = rtrim(rtrim(number_format($amount / 1_000_000, 1, $isId ? ',' : '.', ''), '0'), ',.');

            return 'Rp' . $value . ($isId ? 'jt' : 'm');
        }

        if ($amount >= 1_000) {
            return 'Rp' . rtrim(rtrim(number_format($amount / 1_000, 1, $isId ? ',' : '.', ''), '0'), ',.') . ($isId ? 'rb' : 'k');
        }

        return 'Rp' . $amount;
    }
}
