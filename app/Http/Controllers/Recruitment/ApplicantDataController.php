<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RecruitmentApplicant;
use App\Models\Person; 
use Illuminate\Support\Facades\Storage;

class ApplicantDataController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // 1. Pastikan User terhubung ke tabel Persons
        if (!$user->person_id) {
            // Cek apakah ada person dengan email yang sama di database
            $person = Person::where('email', $user->email)->first();
            
            if (!$person) {
                // Buat Person baru jika belum ada
                $person = Person::create([
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null, 
                ]);
            }
            
            // Hubungkan user ke person
            $user->person_id = $person->id;
            $user->save();
            $user->refresh();
        }

        $person = $user->person; // Data diambil dari tabel persons

        // Ambil data lamaran untuk tab "Lamaran Anda"
        $applications = RecruitmentApplicant::with('recruitmentRequest.positionObj') 
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return view('recruitment.applicant_data.index', compact('user', 'person', 'applications'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Safety check
        if (!$user->person) {
             return back()->with('error', 'Data Person tidak ditemukan. Hubungi admin.');
        }
        
        $person = $user->person; 

        // Validasi (Field utama saja)
        $request->validate([
            'full_name' => 'required|string|max:255',
            'nik' => 'nullable|string|max:20',
            'cv_file' => 'nullable|mimes:pdf|max:2048',
        ]);

        // --- UPDATE DATA PERSON (Single Data) ---
        $person->full_name = $request->full_name;
        $person->nik = $request->nik; 
        $person->phone = $request->phone;
        $person->gender = $request->gender;
        $person->place_of_birth = $request->place_of_birth;
        $person->date_of_birth = $request->date_of_birth;
        $person->religion = $request->religion;
        $person->marital_status = $request->marital_status;
        $person->height = $request->height;
        $person->weight = $request->weight;
        $person->linkedin_url = $request->linkedin_url;
        $person->instagram_url = $request->instagram_url;
        
        $person->address = $request->address_ktp;
        $person->city = $request->city_ktp;
        $person->province_ktp = $request->province_ktp;
        $person->address_domicile = $request->address_domicile;
        $person->city_domicile = $request->city_domicile;
        $person->province_domicile = $request->province_domicile;

        // --- UPDATE DATA JSON (REPEATER) ---
        // Gunakan array_values() untuk mereset index agar tersimpan sebagai Array JSON [{}, {}] yang rapi
        // Sesuaikan filter field-nya!
        
        // 1. Pendidikan (Cek 'name' sekolah)
        $person->education_history = array_values(array_filter($request->education_list ?? [], function($i) {
            return !empty($i['name']) || !empty($i['level']); // Simpan jika Nama ATAU Jenjang terisi
        }));

        // 2. Keluarga (Cek 'name')
        $person->family_data = array_values(array_filter($request->family_list ?? [], function($i) {
            return !empty($i['name']); 
        }));

        // 3. Pengalaman Kerja (Cek 'company') -> INI YANG SEBELUMNYA SALAH
        $person->work_experience = array_values(array_filter($request->work_list ?? [], function($i) {
            return !empty($i['company']); // Field di repeater-work adalah 'company', bukan 'name'
        }));

        // 4. Organisasi (Cek 'name')
        $person->organization_experience = array_values(array_filter($request->org_list ?? [], function($i) {
            return !empty($i['name']);
        }));

        // 5. Skill (Cek 'name')
        $person->skills = array_values(array_filter($request->skill_list ?? [], function($i) {
            return !empty($i['name']);
        }));

        // 6. Sertifikasi (Cek 'name')
        $person->certifications = array_values(array_filter($request->cert_list ?? [], function($i) {
            return !empty($i['name']);
        }));

        // --- UPLOAD FILES ---
        $files = [
            'cv_file' => 'cv_path',
            'photo_file' => 'photo_path',
            'id_card_file' => 'id_card_path',
            'ijazah_file' => 'ijazah_path',
            'transcripts_file' => 'transcripts_path',
            'skck_file' => 'skck_path',
            'health_file' => 'health_cert_path',
            'toefl_file' => 'toefl_path',
        ];

        foreach ($files as $inputName => $dbColumn) {
            if ($request->hasFile($inputName)) {
                if ($person->$dbColumn) Storage::disk('public')->delete($person->$dbColumn);
                $person->$dbColumn = $request->file($inputName)->store('applicants/'.$user->id, 'public');
            }
        }

        $person->save();

        return back()->with('success', 'Biodata berhasil diperbarui.');
    }
}