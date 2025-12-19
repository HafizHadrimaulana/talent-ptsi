<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RecruitmentApplicant;
use Illuminate\Support\Facades\Storage;

class ApplicantDataController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Gunakan RecruitmentApplicant (tabel: recruitment_applicants)
        $applications = RecruitmentApplicant::with('recruitmentRequest.positionObj') 
            ->where('user_id', $user->id) // Kolom user_id ada di tabel ini
            ->latest()
            ->get();

        return view('recruitment.applicant_data.index', compact('user', 'applications'));
    }

    public function update(Request $request)
    {
        $user = Auth::user(); // Ambil data user yang sedang login
        
        $request->validate([
            'name'    => 'required|string|max:255',
            'nik'     => 'nullable|string|max:20',
            'phone'   => 'nullable|string|max:20',
            'cv_file' => 'nullable|mimes:pdf|max:2048',
        ]);

        // Simpan ke Tabel Users (Data Master)
        $user->name = $request->name;
        $user->nik  = $request->nik;
        $user->phone = $request->phone;
        $user->education_level = $request->education_level;
        $user->education = $request->education; // Jurusan
        $user->experience = $request->experience;
        
        if ($request->hasFile('cv_file')) {
            if ($user->cv_path) Storage::disk('public')->delete($user->cv_path);
            $user->cv_path = $request->file('cv_file')->store('profiles/cv', 'public');
        }

        $user->save(); // Sekarang ini BERHASIL karena kolom sudah ada di tabel users

        return back()->with('success', 'Biodata berhasil diperbarui.');
    }
}