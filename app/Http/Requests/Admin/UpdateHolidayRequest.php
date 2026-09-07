<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        return ['holiday_date' => ['required', 'date', Rule::unique('holidays', 'holiday_date')->ignore($this->route('holiday'))], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['required', 'boolean']];
    }
}
