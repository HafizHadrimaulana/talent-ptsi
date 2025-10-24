<?php

namespace App\Support;

use DateTimeImmutable;

class DateSanitizer
{
    /**
     * Terima berbagai format tanggal umum, kembalikan 'Y-m-d' atau null.
     * Contoh yang didukung: '2024-11-30', '30/11/2024', '30-11-2024', '2024/11/30'
     */
    public static function toDateOrNull($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '' || $s === '0000-00-00') return null;

        $candidates = [
            'Y-m-d','d/m/Y','d-m-Y','Y/m/d','m/d/Y','d M Y','d M, Y','Y.m.d','d.m.Y',
        ];

        foreach ($candidates as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $s);
            if ($dt && $dt->format($fmt) === $s) {
                return $dt->format('Y-m-d');
            }
        }

        // fallback: strtotime
        $ts = strtotime($s);
        if ($ts !== false) return date('Y-m-d', $ts);

        return null;
        }
}
