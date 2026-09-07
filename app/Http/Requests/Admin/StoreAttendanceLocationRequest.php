<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'address' => ['required', 'string', 'max:1000'], 'latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180'], 'radius_meters' => ['required', 'integer', 'min:5', 'max:5000'], 'max_accuracy_meters' => ['required', 'integer', 'min:5', 'max:500'], 'is_active' => ['required', 'boolean']];
    }
}
