<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('participant') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxPhotoSize = max(256, min(5120, (int) (Setting::query()->where('key', 'max_photo_size_kb')->value('value') ?: 3072)));

        return ['training_schedule_id' => ['required', 'integer', 'exists:training_schedules,id'], 'device_token' => ['required', 'string', 'min:20', 'max:255'], 'type' => ['required', Rule::in(['morning', 'afternoon'])], 'latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180'], 'accuracy_meters' => ['required', 'numeric', 'min:0', 'max:10000'], 'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'extensions:jpg,jpeg,png,webp', "max:{$maxPhotoSize}", 'dimensions:min_width=240,min_height=240,max_width=4096,max_height=4096']];
    }
}
