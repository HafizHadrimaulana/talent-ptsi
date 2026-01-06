<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RecruitmentApplicant;
use App\Models\Person; 
use Illuminate\Support\Facades\Storage;
use App\Models\Position;

class ApplicantDataController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->person_id) {
            $person = Person::where('email', $user->email)->first();
            if (!$person) {
                $person = Person::create([
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null, 
                ]);
            }
            $user->person_id = $person->id;
            $user->save();
            $user->refresh();
        }

        $person = $user->person;
        $positionsMap = Position::pluck('name', 'id')->toArray();
        $applications = RecruitmentApplicant::with([
                'recruitmentRequest.positionObj',
                'recruitmentRequest.unit'
            ]) 
            ->where('user_id', $user->id)
            ->latest()
            ->get();
        return view('recruitment.applicant_data.index', compact('user', 'person', 'applications', 'positionsMap'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user->person) {
             return back()->with('error', 'Data Person tidak ditemukan. Hubungi admin.');
        }
        $person = $user->person; 
        $request->validate([
            'full_name' => 'required|string|max:255',
            'nik' => 'nullable|string|max:20',
            'cv_file' => 'nullable|mimes:pdf|max:2048',
        ]);

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
        $person->education_history = array_values(array_filter($request->education_list ?? [], function($i) {
            return !empty($i['name']); 
        }));
        $person->family_data = array_values(array_filter($request->family_list ?? [], function($i) {
            return !empty($i['name']); 
        }));
        $person->work_experience = array_values(array_filter($request->work_list ?? [], function($i) {
            return !empty($i['company']);
        }));
        $person->organization_experience = array_values(array_filter($request->org_list ?? [], function($i) {
            return !empty($i['name']);
        }));
        $person->skills = array_values(array_filter($request->skill_list ?? [], function($i) {
            return !empty($i['name']);
        }));
        $person->certifications = array_values(array_filter($request->cert_list ?? [], function($i) {
            return !empty($i['name']);
        }));
        $files = [
            'cv_file' => 'cv_path',
            'photo_file' => 'photo_path',
            'id_card_file' => 'id_card_path',
            'ijazah_file' => 'ijazah_path',
            'transcripts_file' => 'transcripts_path',
            'skck_file' => 'skck_path',
            'health_file' => 'health_cert_path',
            'toefl_file' => 'toefl_path',
            'other_doc_file'   => 'other_doc_path',
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