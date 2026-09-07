<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('training_programs', 'code')->ignore($this->route('training_program'))], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:3000'], 'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'], 'is_active' => ['required', 'boolean']];
    }
}
