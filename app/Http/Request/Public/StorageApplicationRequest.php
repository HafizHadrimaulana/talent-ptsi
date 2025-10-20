<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
  public function authorize(): bool { return true; } // publik

  public function rules(): array {
    return [
      'full_name'  => ['required','string','max:200'],
      'email'      => ['required','email','max:200'],
      'phone'      => ['nullable','string','max:50'],
      'nik_number' => ['nullable','string','max:50'],
      'cv'         => ['required','file','mimes:pdf,doc,docx','max:5120'], // 5MB
      'cover'      => ['nullable','file','mimes:pdf,doc,docx','max:5120'],
      'notes'      => ['nullable','string','max:2000'],
    ];
  }

  public function attributes(): array {
    return ['cv' => 'CV/Resume', 'cover' => 'Surat Lamaran'];
  }
}
