<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller; // Penting: Import base Controller
use Illuminate\Http\Request;
use App\Services\SITMS\SalaryCalculator;

class SalaryController extends Controller
{
    /**
     * Menghitung estimasi gaji, pajak, dan BPJS
     *
     * @param Request $request
     * @param SalaryCalculator $calculator
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculate(Request $request, SalaryCalculator $calculator)
    {
        // 1. Validasi Input
        $request->validate([
            'salary'     => 'required|numeric',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        // 2. Mapping Input dari Request
        $gajiPokok = (float) $request->salary;
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $thr       = (float) $request->input('thr', 0);
        
        $kompensasi = (float) $request->input('kompensasi', 0);

        // 3. Panggil Service Calculator
        $result = $calculator->calculate($gajiPokok, $startDate, $endDate, $thr, $kompensasi);

        // 4. Kembalikan response JSON
        return response()->json($result);
    }
}