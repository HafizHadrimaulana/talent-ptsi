<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class PublishRequest extends FormRequest
{
  public function authorize(): bool { return $this->user()?->can('recruitment.update') ?? false; }

  public function rules(): array {
    return [
      'title'            => ['required','string','max:200'],
      'position'         => ['nullable','string','max:200'],
      'work_location'    => ['nullable','string','max:200'],
      'employment_type'  => ['nullable','string','max:50'],
      'requirements'     => ['nullable','array'],
      'requirements.*'   => ['string','max:500'],
      'is_published'     => ['sometimes','boolean'],
    ];
  }
}
