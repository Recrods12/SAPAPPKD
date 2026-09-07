<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:150'],
            'institution_name' => ['required', 'string', 'max:150'],
            'institution_address' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['required', Rule::in(['Asia/Jakarta'])],
            'default_location_id' => ['nullable', 'integer', Rule::exists('attendance_locations', 'id')->where('is_active', true)],
            'default_radius_meters' => ['required', 'integer', 'min:5', 'max:5000'],
            'default_max_accuracy_meters' => ['required', 'integer', 'min:5', 'max:500'],
            'default_start_time' => ['required', 'date_format:H:i'],
            'default_end_time' => ['required', 'date_format:H:i', 'after:default_start_time'],
            'default_morning_open' => ['required', 'date_format:H:i'],
            'default_morning_on_time_limit' => ['required', 'date_format:H:i', 'after_or_equal:default_morning_open'],
            'default_morning_close' => ['required', 'date_format:H:i', 'after:default_morning_on_time_limit'],
            'default_afternoon_open' => ['required', 'date_format:H:i'],
            'default_afternoon_early_limit' => ['required', 'date_format:H:i', 'after_or_equal:default_afternoon_open'],
            'default_afternoon_close' => ['required', 'date_format:H:i', 'after:default_afternoon_early_limit'],
            'max_photo_size_kb' => ['required', 'integer', 'min:256', 'max:5120'],
            'photo_compression_quality' => ['required', 'integer', 'min:40', 'max:95'],
            'photo_retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
            'mail_from_address' => ['nullable', 'email:rfc', 'max:255'],
            'privacy_notice' => ['required', 'string', 'min:30', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'extensions:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=128,min_height=128,max_width=2000,max_height=2000'],
        ];
    }
}
