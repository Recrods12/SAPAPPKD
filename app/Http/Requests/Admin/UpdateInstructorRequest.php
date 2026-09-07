<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        $instructor = $this->route('instructor');

        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($instructor)], 'password' => ['nullable', 'confirmed', Password::defaults()], 'employee_number' => ['nullable', 'string', 'max:50', Rule::unique('instructor_profiles', 'employee_number')->ignore($instructor?->instructorProfile?->id)], 'phone' => ['required', 'regex:/^(?:\+62|62|0)8[0-9]{7,13}$/'], 'address' => ['nullable', 'string', 'max:1000'], 'is_active' => ['required', 'boolean'], 'account_status' => ['required', Rule::in(['active', 'inactive'])]];
    }
}
