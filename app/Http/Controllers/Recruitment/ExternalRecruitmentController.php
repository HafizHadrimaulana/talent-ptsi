<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\RecruitmentRequest;   
use App\Models\RecruitmentApplicant; 
use App\Models\Position;

class ExternalRecruitmentController extends Controller
{
    public function index(Request $request)
    {
        $me = Auth::user();
        
        // Sesuaikan 'DHC' atau 'Superadmin' sebagai user pusat yang bisa lihat semua
        $isCentralHR = $me->hasAnyRole(['Superadmin', 'DHC', 'VP Human Capital']); 
        
        // Cek apakah user adalah Pelamar
        $isPelamar = $me->hasRole('Pelamar'); 

        // Cek apakah user adalah Admin SDM Unit (Punya Unit ID tapi bukan Pusat)
        $isUnitHR = !$isCentralHR && !$isPelamar && $me->unit_id;

        // QUERY DATA LOWONGAN
        $query = RecruitmentRequest::with(['unit', 'applicants', 'positionObj']) 
            ->where('type', 'Rekrutmen')
            ->where(function($q) {
                $q->where('status', 'approved')
                ->orWhere('status', 'like', '%Selesai%') 
                ->orWhere('status', 'Final');
            });

        // LOGIKA FILTER UNIT (POV Admin Unit vs Pelamar/Pusat)
        
        // Jika Pelamar atau Orang Pusat (DHC), TAMPILKAN SEMUA (kecuali difilter manual)
        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        // Jika Admin SDM Unit (Bukan Pusat & Bukan Pelamar), PAKSA FILTER ke Unit-nya saja
        if ($isUnitHR) {
            $query->where('unit_id', $me->unit_id);
        }

        $vacancies = $query->orderBy('updated_at', 'desc')->paginate(10);

        // Data Lamaran Saya (Khusus POV Pelamar untuk tombol "Lihat Status")
        $myApplications = [];
        if ($isPelamar) {
            $myApplications = RecruitmentApplicant::where('user_id', $me->id)
                ->pluck('recruitment_request_id')
                ->toArray();
        }
        $positionsMap = Position::pluck('name', 'id')->toArray();

        return view('recruitment.external.index', [
            'list'           => $vacancies,
            'isDHC'          => ($isCentralHR || $isUnitHR), // akses fitur kelola pelamar
            'isPelamar'      => $isPelamar,
            'myApplications' => $myApplications,
            'positionsMap'   => $positionsMap
        ]);
    }

    // --- FUNGSI APPLY & UPDATE STATUS ---

    public function apply(Request $request)
    {
        $request->validate([
            'recruitment_request_id' => 'required|exists:recruitment_requests,id',
            'name'       => 'required|string',
            'email'      => 'required|email',
            'phone'      => 'required|string',
            'university' => 'required|string',
            'major'      => 'required|string',
            'cv_file'    => 'required|mimes:pdf|max:2048'
        ]);

        $path = null;
        if ($request->hasFile('cv_file')) {
            $file = $request->file('cv_file');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('cv_uploads', $filename, 'public');
        }

        RecruitmentApplicant::create([
            'recruitment_request_id' => $request->recruitment_request_id,
            'user_id'    => Auth::id(),
            'name'       => $request->name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'university' => $request->university,
            'major'      => $request->major,
            'cv_path'    => $path,
            'status'     => 'Screening CV'
        ]);

        return redirect()->back()->with('ok', 'Lamaran berhasil dikirim! Silakan pantau status Anda.');
    }

    public function getApplicants($requestId)
    {
        $applicants = RecruitmentApplicant::where('recruitment_request_id', $requestId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $applicants]);
    }

    public function updateApplicantStatus(Request $request, $applicantId)
    {
        $app = RecruitmentApplicant::findOrFail($applicantId);
        
        $app->status = $request->status;
        $app->hr_notes = $request->notes;

        if (str_contains($request->status, 'Interview')) {
            $app->interview_schedule = $request->interview_schedule; 
        }

        $app->save();

        return redirect()->back()->with('ok', 'Status pelamar berhasil diperbarui.');
    }
}