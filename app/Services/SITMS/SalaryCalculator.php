<?php
namespace App\Services\SITMS;
use Carbon\Carbon;

class SalaryCalculator
{
    /**
     * Menghitung remunerasi, pajak, dan BPJS sesuai aturan Izin Prinsip.
     * @param float $gajiPokok
     * @param string $startDate 
     * @param string $endDate   
     * @param float $thr        
     * @param float $kompensasi 
     * @return array
     * @param string $riskLevel 'Tinggi' atau 'Rendah'
     */
    public function calculate(float $gajiPokok, string $startDate, string $endDate, float $thr = 0, float $kompensasi = 0, string $riskLevel = 'Rendah'): array
    {
        // Hitung Durasi (+1 hari agar inklusif)
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);
        $monthsRaw = $start->floatDiffInMonths($end->copy()->addDay());
        $duration = (int) floor($monthsRaw);

        if ($duration < 1) $duration = 1;
        $totalRemunerasi = round(($gajiPokok * $duration) + $thr + $kompensasi);

        $basisKes = ($gajiPokok >= 12000000) ? 12000000 : $gajiPokok;
        $bpjsKesehatan = $basisKes * 0.04;

        $rateBpjsTk = ($riskLevel === 'Tinggi') ? 0.0574 : 0.0424;
        
        // Komponen JKK, JKM, JHT (Tanpa Cap)
        // $jkk_jkm_jht = $gajiPokok * $rateBpjsTk; 
        
        // Komponen JP (Jaminan Pensiun) - Tetap 2% dengan Cap Rp 10.547.400
        // $capJp = 10547400;
        // $basisJp = ($gajiPokok >= $capJp) ? $capJp : $gajiPokok;
        // $jp = $basisJp * 0.02;

        // Total BPJS Ketenagakerjaan
        $bpjsKetenagakerjaan = $gajiPokok * $rateBpjsTk;

        // \Illuminate\Support\Facades\Log::info("DEBUG SALARY:", [
        //     'Input_Gaji' => $gajiPokok,
        //     'Input_THR' => $thr,
        //     'Input_Komp' => $kompensasi,
        //     'Durasi_Bulan' => $duration,
        //     'Total_Remunerasi' => $totalRemunerasi,
        //     'Resiko' => $riskLevel,
        //     'Rate_TK' => $rateBpjsTk,
        //     'Total_BPJS_TK' => $bpjsKetenagakerjaan
        // ]);

        $tarif = $this->getTarifTerA($totalRemunerasi);
        $pajakPerBulan = ($totalRemunerasi * $tarif) / $duration;

        return [
            'duration_months' => $duration,
            'gaji_pokok'      => $gajiPokok,
            'thr'             => $thr,
            'kompensasi'      => $kompensasi,
            'total_remunerasi'=> $totalRemunerasi,
            'tarif_pajak'     => $tarif * 100,
            'pph21_bulanan'   => round($pajakPerBulan),
            'bpjs_kesehatan'  => round($bpjsKesehatan),
            'bpjs_ketenagakerjaan' => round($bpjsKetenagakerjaan)
        ];
    }

    private function getTarifTerA($income)
    {
        // Format: [Batas Bawah, Batas Atas, Tarif]
        $brackets = [
            [0,         5400000,  0.00],
            [5400001,   5650000,  0.0025],
            [5650001,   5950000,  0.005],
            [5950001,   6300000,  0.0075],
            [6300001,   6750000,  0.01],
            [6750001,   7500000,  0.0125],
            [7500001,   8550000,  0.015],
            [8550001,   9650000,  0.0175],
            [9650001,   10050000, 0.02],
            [10050001,  10350000, 0.0225],
            [10350001,  10700000, 0.025],
            [10700001,  11050000, 0.03],
            [11050001,  11600000, 0.035],
            [11600001,  12500000, 0.04],
            [12500001,  13750000, 0.05],
            [13750001,  15100000, 0.06],
            [15100001,  16950000, 0.07],
            [16950001,  19750000, 0.08],
            [19750001,  24150000, 0.09],
            [24150001,  26450000, 0.10],
            [26450001,  28000000, 0.11],
            [28000001,  30050000, 0.12],
            [30050001,  32400000, 0.13],
            [32400001,  35400000, 0.14],
            [35400001,  39100000, 0.15],
            [39100001,  43850000, 0.16],
            [43850001,  47800000, 0.17],
            [47800001,  51400000, 0.18],
            [51400001,  56300000, 0.19],
            [56300001,  62200000, 0.20],
            [62200001,  68600000, 0.21],
            [68600001,  77500000, 0.22],
            [77500001,  89000000, 0.23],
            [89000001, 103000000, 0.24],
            [103000001, 125000000, 0.25],
            [125000001, 157000000, 0.26],
            [157000001, 206000000, 0.27],
            [206000001, 337000000, 0.28],
            [337000001, 454000000, 0.29],
            [454000001, 550000000, 0.30],
            [550000001, 695000000, 0.31],
            [695000001, 910000000, 0.32],
            [910000001, 1400000000, 0.33],
            [1400000001, 999999999999, 0.34],
        ];

        foreach ($brackets as $range) {
            if ($income >= $range[0] && $income <= $range[1]) {
                return $range[2];
            }
        }        
        return 0.34; 
    }
}