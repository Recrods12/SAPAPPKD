<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');

        return $attendance && $this->user()?->instructedClasses()->whereKey($attendance->trainingSchedule->training_class_id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['requested_status' => ['required', 'in:on_time,late,present,early_leave,needs_verification,outside_schedule,void'], 'reason' => ['required', 'string', 'min:10', 'max:1000']];
    }
}
