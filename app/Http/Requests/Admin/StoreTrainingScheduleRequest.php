<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        return ['training_class_id' => ['required', Rule::exists('training_classes', 'id')->whereNull('deleted_at')], 'attendance_location_id' => ['required', Rule::exists('attendance_locations', 'id')->whereNull('deleted_at')->where('is_active', true)], 'schedule_date' => ['required', 'date', Rule::unique('training_schedules')->where('training_class_id', $this->integer('training_class_id'))], 'start_time' => ['required', 'date_format:H:i'], 'end_time' => ['required', 'date_format:H:i', 'after:start_time'], 'morning_open' => ['required', 'date_format:H:i'], 'morning_on_time_limit' => ['required', 'date_format:H:i', 'after_or_equal:morning_open'], 'morning_close' => ['required', 'date_format:H:i', 'after:morning_on_time_limit'], 'afternoon_open' => ['required', 'date_format:H:i', 'after:morning_close'], 'afternoon_early_limit' => ['required', 'date_format:H:i', 'after_or_equal:afternoon_open'], 'afternoon_close' => ['required', 'date_format:H:i', 'after:afternoon_early_limit'], 'subject' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:1000'], 'status' => ['required', Rule::in(['active', 'cancelled'])]];
    }
}
