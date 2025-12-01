<?php

namespace App\Support;

class TicketNumberGenerator
{
    /**
     * Generate nomor ticket dengan format: UNITCODE-YY-MM-4RANDOM
     *
     * @param string $unitCode
     * @param \DateTimeInterface|null untuk tahun/bulan
     * @return string
     */
    public static function generate(string $unitCode, ?\DateTimeInterface $createdAt = null): string
    {
        $dt = $createdAt ?? new \DateTimeImmutable();
        $yy = $dt->format('y');
        $mm = $dt->format('m');

        $randomPart = self::generateRandomString(4);
        return strtoupper($unitCode) . '-' . $yy  . $mm . '-' . $randomPart;
    }

    /**
     * Generate 4 karakter random (huruf dan angka)
     * 
     * @param int $length
     * @return string
     */
    private static function generateRandomString(int $length = 4): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $randomString;
    }
}
