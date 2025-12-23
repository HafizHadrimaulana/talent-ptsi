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
        
        // 1. Cek Role User
        $isCentralHR = $me->hasAnyRole(['Superadmin', 'DHC', 'VP Human Capital']); 
        $isPelamar = $me->hasRole('Pelamar'); 
        $isUnitHR = !$isCentralHR && !$isPelamar && $me->unit_id;

        // 2. Ambil Parameter Filter & Pagination
        $q = $request->input('q');             // Kata kunci pencarian
        $perPage = $request->input('per_page', 10); // Default 10 data per halaman

        // 3. Query Dasar
        $query = RecruitmentRequest::with(['unit', 'applicants', 'positionObj']) 
            ->where('type', 'Rekrutmen')
            ->where(function($qq) {
                $qq->where('status', 'approved')
                ->orWhere('status', 'like', '%Selesai%') 
                ->orWhere('status', 'Final');
            });

        // 4. Logika Pencarian (Search Box)
        if ($q) {
            $query->where(function($sub) use ($q) {
                // A. Cari berdasarkan Nomor Tiket
                $sub->where('ticket_number', 'like', "%{$q}%")
                
                    // B. Cari berdasarkan NAMA POSISI (Masuk ke tabel positions)
                    ->orWhereHas('positionObj', function($p) use ($q) {
                        $p->where('name', 'like', "%{$q}%");
                    })

                    // C. Fallback: Cari di kolom position tabel utama (untuk jaga-jaga atau search ID)
                    ->orWhere('position', 'like', "%{$q}%")

                    // D. Cari berdasarkan NAMA UNIT
                    ->orWhereHas('unit', function($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%");
                    });
            });
        }

        // 5. Logika Filter Unit (Berdasarkan Role)
        
        // Jika Pelamar atau Orang Pusat (DHC), TAMPILKAN SEMUA (kecuali difilter manual via URL)
        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        // Jika Admin SDM Unit (Bukan Pusat & Bukan Pelamar), PAKSA FILTER ke Unit-nya saja
        if ($isUnitHR) {
            $query->where('unit_id', $me->unit_id);
        }

        // 6. Eksekusi Pagination
        // Gunakan $perPage dari input user
        $vacancies = $query->orderBy('updated_at', 'desc')
                           ->paginate($perPage)
                           ->withQueryString(); // Agar parameter search tidak hilang saat klik page 2

        // 7. Data Pendukung Lainnya
        $myApplications = [];
        if ($isPelamar) {
            $myApplications = RecruitmentApplicant::where('user_id', $me->id)
                ->pluck('recruitment_request_id')
                ->toArray();
        }
        $positionsMap = Position::pluck('name', 'id')->toArray();

        return view('recruitment.external.index', [
            'list'           => $vacancies,
            'isDHC'          => ($isCentralHR || $isUnitHR), 
            'isPelamar'      => $isPelamar,
            'myApplications' => $myApplications,
            'positionsMap'   => $positionsMap
        ]);
    }

    // --- FUNGSI LAINNYA TIDAK PERLU DIUBAH (SAMA SEPERTI SEBELUMNYA) ---

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