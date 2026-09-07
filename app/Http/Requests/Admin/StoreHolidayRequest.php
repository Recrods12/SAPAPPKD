<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        return ['holiday_date' => ['required', 'date', 'unique:holidays,holiday_date'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['required', 'boolean']];
    }
}
