<?php

namespace App\Http\Controllers\Recruitment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SITMS\SalaryCalculator;

class SalaryController extends Controller
{
    /**
     * @param Request $request
     * @param SalaryCalculator $calculator
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculate(Request $request, SalaryCalculator $calculator)
    {
        $request->validate([
            'salary'     => 'required|numeric',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            
        ]);
        $gajiPokok = (float) $request->salary;
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $thr       = (float) $request->input('thr', 0);
        $kompensasi = (float) $request->input('kompensasi', 0);
        $riskLevel = $request->input('risk_level', 'Rendah');
        $result = $calculator->calculate($gajiPokok, $startDate, $endDate, $thr, $kompensasi, $riskLevel);
        return response()->json($result);
    }
}