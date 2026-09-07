<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'confirmed', Password::defaults()], 'employee_number' => ['nullable', 'string', 'max:50', 'unique:instructor_profiles,employee_number'], 'phone' => ['required', 'regex:/^(?:\+62|62|0)8[0-9]{7,13}$/'], 'address' => ['nullable', 'string', 'max:1000'], 'is_active' => ['required', 'boolean']];
    }
}
