<?php

namespace App\Http\Controllers\Recruitment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RecruitmentRequest;    
use App\Models\RecruitmentApplicant; 
use App\Models\Position;
use Barryvdh\DomPDF\Facade\Pdf;

class ExternalRecruitmentController extends Controller
{
    public function index(Request $request)
    {
        $me = Auth::user();
        $isCentralHR = $me->hasAnyRole(['Superadmin', 'DHC', 'VP Human Capital']);
        $isPelamar = $me->hasRole('Pelamar');
        $isUnitHR = !$isCentralHR && !$isPelamar && $me->unit_id;
        $isDHC = ($isCentralHR || $isUnitHR);
        if ($request->ajax()) {
            $query = RecruitmentRequest::with(['unit', 'applicants', 'positionObj'])
                ->where('type', 'Rekrutmen')
                ->where(function ($qq) {
                    $qq->where('status', 'approved')
                        ->orWhere('status', 'like', '%Selesai%')
                        ->orWhere('status', 'Final');
                });
            if ($isPelamar) {
                $query->where('is_published', 1);
            }
            if ($isUnitHR) {
                $query->where('unit_id', $me->unit_id);
            }
            if ($request->has('search') && !empty($request->input('search.value'))) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                        ->orWhereHas('positionObj', function ($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%");
                        })
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhereHas('unit', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%");
                        });
                });
            }
            if ($request->has('order')) {
                $order = $request->input('order')[0];
                $colIdx = $order['column'];
                $dir = $order['dir'];
                $cols = ['ticket_number', 'position', 'unit_id', 'is_published', 'headcount', null, null];
                if ($isPelamar) {
                    $cols = ['position', 'unit_id', 'is_published', null];
                }
                if (isset($cols[$colIdx]) && $cols[$colIdx]) {
                    if ($cols[$colIdx] === 'unit_id') {
                        $query->join('units', 'recruitment_requests.unit_id', '=', 'units.id')
                              ->orderBy('units.name', $dir);
                    } else {
                        $query->orderBy($cols[$colIdx], $dir);
                    }
                } else {
                    $query->orderBy('updated_at', 'desc');
                }
            } else {
                $query->orderBy('updated_at', 'desc');
            }
            $countTotal = $query->count();
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $data = $query->skip($start)->take($length)->get();
            $myApplications = collect([]);
            if ($isPelamar) {
                $myApplications = RecruitmentApplicant::where('user_id', $me->id)
                    ->get()
                    ->groupBy('recruitment_request_id');
            }
            $formattedData = $data->map(function ($row) use ($isDHC, $isPelamar, $myApplications) {
                return $this->formatVacancyRow($row, $isDHC, $isPelamar, $myApplications);
            });
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => RecruitmentRequest::where('type', 'Rekrutmen')->count(),
                'recordsFiltered' => $countTotal,
                'data' => $formattedData
            ]);
        }
        return view('recruitment.external.index', [
            'isDHC'      => $isDHC,
            'isPelamar'  => $isPelamar,
        ]);
    }
    private function formatVacancyRow($row, $isDHC, $isPelamar, $myApplications)
    {
        $allPositions = [];
        $details = $row->meta['recruitment_details'] ?? [];
        if (!empty($details) && is_array($details) && count($details) > 0) {
            foreach ($details as $d) {
                $allPositions[] = $d['position_text'] ?? $d['position'] ?? '-';
            }
        } else {
            $allPositions[] = $row->positionObj->name ?? $row->position ?? '-';
        }
        $userApps = $myApplications->get($row->id) ?? collect([]);
        $appliedPositions = $userApps->pluck('position_applied')->filter()->toArray();
        if ($userApps->count() > 0 && count($appliedPositions) === 0) {
            $appliedPositions = $allPositions;
        }
        $availablePositions = array_diff($allPositions, $appliedPositions);
        $availableJson = [];
        foreach ($availablePositions as $p) {
            $availableJson[] = ['name' => $p, 'id' => $p];
        }
        $colTicket = $row->ticket_number ? '<span class="u-badge u-badge--glass">' . $row->ticket_number . '</span>' : '-';
        $colPosisi = '<div class="u-font-bold text-sm">';
        if (count($allPositions) > 1) {
            $colPosisi .= '<ul class="list-disc list-inside text-gray-700">';
            foreach ($allPositions as $pos) {
                $colPosisi .= '<li>' . e($pos);
                if (in_array($pos, $appliedPositions)) {
                    $colPosisi .= ' <i class="fas fa-check-circle text-green-500 text-xs ml-1" title="Sudah dilamar"></i>';
                }
                $colPosisi .= '</li>';
            }
            $colPosisi .= '</ul>';
        } else {
            $colPosisi .= e($allPositions[0]);
            if (count($appliedPositions) > 0) { 
                 $colPosisi .= ' <i class="fas fa-check-circle text-green-500 text-xs ml-1" title="Sudah dilamar"></i>';
            }
        }
        $colPosisi .= '</div>';
        $colUnit = $row->unit->name ?? '-';
        if ($row->is_published) {
            $colStatus = '<span class="u-badge u-badge--success"><i class="fas fa-check-circle u-mr-xs"></i> Dibuka</span>';
        } else {
            $colStatus = '<span class="u-badge u-badge--danger"><i class="fas fa-ban u-mr-xs"></i> Ditutup</span>';
        }
        $colKuota = $row->headcount . ' Orang';
        $colPelamar = '<span class="u-badge u-badge--info"><i class="fas fa-users u-mr-xs"></i> ' . $row->applicants->count() . '</span>';
        $colAksi = '<div class="flex flex-col gap-2 items-end">';
        if ($isDHC) {
            $colAksi .= '<button class="u-btn u-btn--sm u-btn--primary u-btn--outline" onclick="openManageModal(' . $row->id . ', \'' . $row->ticket_number . '\')">
                            <i class="fas fa-users-cog u-mr-xs"></i> Kelola Pelamar
                         </button>';
            $jsonRow = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            $colAksi .= "<button class='u-btn u-btn--sm u-btn--warning u-btn--outline' onclick='openEditVacancyModal({$row->id}, {$jsonRow})'>
                            <i class='fas fa-edit u-mr-xs'></i> Edit/Buka/Tutup
                         </button>";
        } elseif ($isPelamar) {
            if (count($availableJson) > 0) {
                $jsonRow = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                $jsonAvail = htmlspecialchars(json_encode($availableJson), ENT_QUOTES, 'UTF-8');
                $colAksi .= "<button class='u-btn u-btn--sm u-btn--info u-btn--outline' onclick='openVacancyDetail({$row->id}, {$jsonRow}, {$jsonAvail})'>
                                <i class='fas fa-file-alt u-mr-xs'></i> Lihat Deskripsi
                             </button>";
            }
            foreach ($userApps as $app) {
                $status = $app->status ?? '-';
                $date = $app->interview_schedule ?? '';
                $note = htmlspecialchars($app->hr_notes ?? '', ENT_QUOTES);
                $colAksi .= "<button class='u-btn u-btn--sm u-btn--ghost u-text-brand border border-blue-200 u-mt-xs' type='button' 
                                onclick='openMyStatusModal(this)' 
                                data-status='{$status}' 
                                data-date='{$date}' 
                                data-note='{$note}'>
                                <i class='fas fa-info-circle u-mr-xs'></i> Status
                             </button>";
            }
        }
        $colAksi .= '</div>';
        if ($isPelamar) {
            return [$colPosisi, $colUnit, $colStatus, $colAksi];
        } else {
            return [$colTicket, $colPosisi, $colUnit, $colStatus, $colKuota, $colPelamar, $colAksi];
        }
    }

    public function getApplicants($requestId)
    {
        $applicants = RecruitmentApplicant::where('recruitment_request_id', $requestId)
            ->orderBy('created_at', 'desc')
            ->get();
        $data = $applicants->map(function($app) {
             $cvBtn = $app->cv_path 
                ? "<a href='/storage/{$app->cv_path}' target='_blank' class='u-text-brand u-font-bold hover:u-underline'><i class='fas fa-file-pdf'></i> PDF</a>" 
                : '-';
             $badgeClass = 'st-screening';
             if(str_contains($app->status, 'Interview') || str_contains($app->status, 'Psikotes')) $badgeClass = 'st-interview';
             if($app->status === 'Passed' || $app->status === 'Hired') $badgeClass = 'st-passed';
             if(in_array($app->status, ['Rejected', 'Failed', 'Ditolak'])) $badgeClass = 'st-rejected';
             $statusHtml = "<span class='status-badge {$badgeClass}'>{$app->status}</span>";
             $dateShow = $app->interview_schedule ? \Carbon\Carbon::parse($app->interview_schedule)->format('d M Y H:i') : '-';
             $actions = "<div class='u-flex u-gap-xs u-justify-end'>
                            <button class='u-btn u-btn--xs u-btn--info u-btn--outline' onclick='openBiodataModal({$app->id})' title='Lihat Biodata Lengkap'><i class='fas fa-id-card'></i> Bio</button>
                            <button class='u-btn u-btn--xs u-btn--outline' onclick='openUpdateStatus({$app->id}, \"{$app->status}\")' title='Update Status'><i class='fas fa-edit'></i> Proses</button>
                         </div>";
             return [
                 'name_info' => "<div class='u-font-bold'>{$app->name}</div><div class='u-text-xs u-muted'>{$app->email}</div><div class='u-text-xs u-muted'>{$app->phone}</div>",
                 'edu_info' => "<div class='u-text-sm u-font-medium'>{$app->major}</div><div class='u-text-xs u-muted'>{$app->university}</div>",
                 'cv' => $cvBtn,
                 'status' => $statusHtml,
                 'schedule' => "<div class='u-text-xs'>{$dateShow}</div>",
                 'actions' => $actions
             ];
        });
        return response()->json(['data' => $data]);
    }
    public function apply(Request $request)
    {
        $request->validate(['recruitment_request_id' => 'required|exists:recruitment_requests,id', 'position_applied' => 'required|string', 'name' => 'required|string', 'email' => 'required|email', 'phone' => 'required|string', 'university' => 'required|string', 'major' => 'required|string', 'cv_file' => 'required|mimes:pdf|max:2048']);
        $path = null;
        if ($request->hasFile('cv_file')) {
            $file = $request->file('cv_file');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('cv_uploads', $filename, 'public');
        }
        RecruitmentApplicant::create(['recruitment_request_id' => $request->recruitment_request_id, 'user_id' => Auth::id(), 'name' => $request->name, 'position_applied' => $request->position_applied, 'email' => $request->email, 'phone' => $request->phone, 'university' => $request->university, 'major' => $request->major, 'cv_path' => $path, 'status' => 'Screening CV']);
        return redirect()->back()->with('ok', 'Lamaran berhasil dikirim! Silakan pantau status Anda.');
    }
    public function updateApplicantStatus(Request $request, $applicantId)
    {
        $app = RecruitmentApplicant::findOrFail($applicantId);
        $app->status = $request->status;
        $app->hr_notes = $request->notes;
        $statusesWithSchedule = ['Psikotes', 'FGD', 'Interview HR', 'Tes Teknis', 'Interview User', 'Medical Check-Up'];
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
        $me = Auth::user();
        if (!$me->hasAnyRole(['Superadmin', 'DHC', 'SDM Unit']) && $me->unit_id != $req->unit_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'description' => 'required|string',
            'publish_start_date' => 'required|date',
            'publish_end_date' => 'required|date|after_or_equal:publish_start_date',
            'publish_location' => 'required|string|max:255',
        ]);
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
        $me = Auth::user();
        if (!$me->hasAnyRole(['Superadmin', 'DHC', 'SDM Unit']) && $me->unit_id != $req->unit_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $req->update([
            'is_published' => 0,
            // 'published_at' => null // Opsional: jika ingin menghapus riwayat tanggal publish
        ]);

        return response()->json(['success' => true, 'message' => 'Lowongan berhasil ditutup (Unpublished).']);
    }
    public function publish($id)
    {
        $req = RecruitmentRequest::findOrFail($id);

        $me = Auth::user();
        if (!$me->hasAnyRole(['Superadmin', 'DHC', 'SDM Unit']) && $me->unit_id != $req->unit_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $req->update([
            'is_published' => 1
        ]);
        return response()->json(['success' => true, 'message' => 'Lowongan berhasil dibuka kembali (Published).']);
    }
}