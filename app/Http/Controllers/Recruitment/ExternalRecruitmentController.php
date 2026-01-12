<?php

namespace App\Http\Controllers\Recruitment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\RecruitmentRequest;    
use App\Models\RecruitmentApplicant; 
use App\Models\Position;
use App\Models\Person;
use Barryvdh\DomPDF\Facade\Pdf;

class ExternalRecruitmentController extends Controller
{
    public function index(Request $request)
    {
        $me = Auth::user();
        $isCentralHR = $me->hasAnyRole(['Superadmin', 'DHC', 'VP Human Capital']); 
        $isPelamar = $me->hasRole('Pelamar'); 
        $isUnitHR = !$isCentralHR && !$isPelamar && $me->unit_id;
        $q = $request->input('q');
        $perPage = $request->input('per_page', 10);
        $query = RecruitmentRequest::with(['unit', 'applicants', 'positionObj']) 
            ->where('type', 'Rekrutmen')
            ->where(function($qq) {
                $qq->where('status', 'approved')
                ->orWhere('status', 'like', '%Selesai%') 
                ->orWhere('status', 'Final');
            });
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('ticket_number', 'like', "%{$q}%")
                    ->orWhereHas('positionObj', function($p) use ($q) {
                        $p->where('name', 'like', "%{$q}%");
                    })
                    ->orWhere('position', 'like', "%{$q}%")
                    ->orWhereHas('unit', function($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%");
                    });
            });
        }
        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }
        if ($isUnitHR) {
            $query->where('unit_id', $me->unit_id);
        }
        $vacancies = $query->orderBy('updated_at', 'desc')
                           ->paginate($perPage)
                           ->withQueryString();
        
        $myApplications = collect([]); 
        if ($isPelamar) {
            $myApplications = RecruitmentApplicant::where('user_id', $me->id)
                ->get()
                ->groupBy('recruitment_request_id');
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

    public function apply(Request $request)
    {
        $request->validate(['recruitment_request_id' => 'required|exists:recruitment_requests,id', 'name'=>'required|string','email'=>'required|email','phone'=>'required|string','university'=>'required|string','major'=>'required|string','cv_file'=>'required|mimes:pdf|max:2048']);
        $path = null;
        if ($request->hasFile('cv_file')) {
            $file = $request->file('cv_file');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('cv_uploads', $filename, 'public');
        }
        RecruitmentApplicant::create(['recruitment_request_id'=> $request->recruitment_request_id,'user_id'=> Auth::id(),'name'=> $request->name,'email'=> $request->email,'phone'=> $request->phone,'university'=> $request->university,'major'=> $request->major,'cv_path'=> $path,'status'=>'Screening CV']);
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
        $statusesWithSchedule = [
            'Psikotes', 
            'FGD', 
            'Interview HR',
            'Tes Teknis',
            'Interview User', 
            'Medical Check-Up'
        ];
        if (in_array($request->status, $statusesWithSchedule) || str_contains($request->status, 'Interview')) {
            $app->interview_schedule = $request->interview_schedule; 
        } else {
            $app->interview_schedule = null; 
        }

        $app->save();

        return redirect()->back()->with('ok', 'Status pelamar berhasil diperbarui.');
    }

    public function showApplicantBiodata($applicantId)
    {
        $applicant = RecruitmentApplicant::with('user.person')->findOrFail($applicantId);   
        $person = $applicant->user->person;
        if (!$person) {
            return '<div class="u-p-md u-text-center u-text-danger">Data Biodata belum dilengkapi pelamar.</div>';
        }
        return view('recruitment.external.components.biodata-readonly', compact('person', 'applicant'));
    }

    public function downloadBiodataPdf($applicantId)
    {
        $applicant = RecruitmentApplicant::with('user.person')->findOrFail($applicantId);
        $person = $applicant->user->person;
        if (!$person) {
            return back()->with('error', 'Data biodata tidak ditemukan.');
        }
        $pdfConfig = config('recruitment.pdf');
        if (isset($pdfConfig['templates']['BIODATA']['margin_cm'])) {
            $pdfConfig['margin_cm'] = $pdfConfig['templates']['BIODATA']['margin_cm'];
        }
        $disk = $pdfConfig['letterhead_disk'] ?? 'public';
        $path = $pdfConfig['letterhead_path'] ?? 'recruitment/kop-surat.jpg';
        $lhImg = $this->pdfDataUri($disk, $path);
        $pdf = Pdf::loadView('recruitment.external.pdf_biodata', [
            'person'    => $person,
            'applicant' => $applicant,
            'config'    => $pdfConfig,
            'base64Kop' => $lhImg   
        ]);
        $page = $pdfConfig['page'] ?? [];
        $paper = $page['paper'] ?? 'a4';
        $orientation = $page['orientation'] ?? 'portrait';
        $dpi = $pdfConfig['dompdf']['dpi'] ?? 96;
        $pdf->setPaper($paper, $orientation);
        $dom = $pdf->getDomPDF();
        $dom->set_option('dpi', $dpi);
        $dom->set_option('isRemoteEnabled', true);
        $dom->set_option('isHtml5ParserEnabled', true);
        $fileName = 'CV_' . str_replace(' ', '_', $person->full_name) . '.pdf';
        return $pdf->download($fileName);
    }

    protected function pdfDataUri($disk, $path)
    {
        $cleanPath = ltrim($path, '/');
        $possiblePaths = [
            storage_path('app/public/' . $cleanPath),
            public_path('storage/' . $cleanPath),
            public_path($cleanPath),
            base_path('storage/app/public/' . $cleanPath),
            base_path('public/storage/' . $cleanPath),
        ];
        foreach ($possiblePaths as $fullPath) {
            if (file_exists($fullPath)) {
                $bin = file_get_contents($fullPath);
                if ($bin === false) continue; 
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $mime = 'image/jpeg';
                if ($ext === 'png') {
                    $mime = 'image/png';
                }
                return "data:{$mime};base64," . base64_encode($bin);
            }
        }
        return null;
    }
    public function updateDescription(Request $request, $id)
    {
        $req = RecruitmentRequest::findOrFail($id);
        
        // Validasi hak akses
        $me = Auth::user();
        if (!$me->hasAnyRole(['Superadmin', 'DHC', 'SDM Unit']) && $me->unit_id != $req->unit_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Validasi Input
        $request->validate([
            'description' => 'required|string',
            'publish_start_date' => 'required|date',
            'publish_end_date' => 'required|date|after_or_equal:publish_start_date',
            'publish_location' => 'required|string|max:255',
        ]);

        // Simpan Perubahan
        $req->update([
            'description' => $request->description,
            'publish_start_date' => $request->publish_start_date,
            'publish_end_date' => $request->publish_end_date,
            'publish_location' => $request->publish_location
        ]);

        return response()->json(['success' => true, 'message' => 'Data lowongan berhasil diperbarui!']);
    }

    public function unpublish($id)
    {
        $req = RecruitmentRequest::findOrFail($id);

        // Validasi hak akses
        $me = Auth::user();
        if (!$me->hasAnyRole(['Superadmin', 'DHC', 'SDM Unit']) && $me->unit_id != $req->unit_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $req->update([
            'is_published' => 0, // Set ke 0 agar tidak tampil di landing page
            // 'published_at' => null // Opsional: jika ingin menghapus riwayat tanggal publish
        ]);

        return response()->json(['success' => true, 'message' => 'Lowongan berhasil ditutup (Unpublished).']);
    }
}