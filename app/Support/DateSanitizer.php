<?php

namespace App\Support;

use Carbon\Carbon;

class DateSanitizer
{
    public static function toDateOrNull($value): ?string
    {
        if (empty($value)) return null;
        $v = trim((string)$value);
        $invalids = ['0000-00-00','0000-00-00 00:00:00','9999-12-31','1900-12-31'];
        if (in_array($v, $invalids, true)) return null;
        try { return Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable $e) { return null; }
    }

    public static function toDateTimeOrNull($value): ?string
    {
        if (empty($value)) return null;
        $v = trim((string)$value);
        $invalids = ['0000-00-00','0000-00-00 00:00:00','9999-12-31','1900-12-31'];
        if (in_array($v, $invalids, true)) return null;
        try { return Carbon::parse($v)->format('Y-m-d H:i:s'); } catch (\Throwable $e) { return null; }
    }

    /**
     * Ambil 4 digit tahun pertama yang muncul (1900–2099).
     * Contoh:
     * - "2014-"   -> 2014
     * - "2022.2"  -> 2022
     * - "20221"   -> 2022
     * - "2013-2016" -> 2013
     */
    public static function toYearOrNull($value): ?string
    {
        if (empty($value)) return null;
        $v = trim((string)$value);

        if (preg_match('/(19|20)\d{2}/', $v, $m)) {
            return $m[0];
        }
        return null;
    }

    public static function strOrNull($v, ?int $max = null): ?string
    {
        $v = trim((string)($v ?? ''));
        if ($v === '' || $v === '?' || strcasecmp($v, 'N/A') === 0) return null;
        return $max ? mb_substr($v, 0, $max) : $v;
    }
}
