<?php

namespace App\Http\Requests\Auth;

use App\Models\RegistrationCode;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterParticipantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits_between:16,20', 'unique:participant_profiles,nik'],
            'participant_number' => ['required', 'string', 'max:50', 'unique:participant_profiles,participant_number'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:2000'],
            'phone' => ['required', 'regex:/^08[0-9]{8,13}$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'alpha_dash', 'min:4', 'max:50', 'unique:users,username'],
            'training_program_id' => ['required', 'integer', 'exists:training_programs,id'],
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'training_class_id' => ['required', 'integer', 'exists:training_classes,id'],
            'registration_code' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                $code = RegistrationCode::query()->where('code', $value)->first();
                if (! $code || ! $code->is_active || ($code->expires_at && $code->expires_at->isPast()) || ($code->usage_limit && $code->usage_count >= $code->usage_limit)) {
                    $fail('Kode registrasi tidak valid, kedaluwarsa, atau kuotanya telah habis.');
                }
            }],
            'password' => ['required', 'confirmed', Password::defaults()],
            'privacy_consent' => ['accepted'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $batchMatches = TrainingBatch::query()->whereKey($this->integer('training_batch_id'))->where('training_program_id', $this->integer('training_program_id'))->where('status', 'open')->exists();
            if (! $batchMatches) {
                $validator->errors()->add('training_batch_id', 'Angkatan tidak termasuk dalam program yang dipilih.');
            }
            $classMatches = TrainingClass::query()->whereKey($this->integer('training_class_id'))->where('training_batch_id', $this->integer('training_batch_id'))->where('is_active', true)->exists();
            if (! $classMatches) {
                $validator->errors()->add('training_class_id', 'Kelas tidak termasuk dalam angkatan yang dipilih.');
            } elseif (TrainingClass::query()->whereKey($this->integer('training_class_id'))->whereHas('enrollments', fn ($query) => $query->whereIn('status', ['pending', 'active']), '>=', TrainingClass::query()->whereKey($this->integer('training_class_id'))->value('capacity'))->exists()) {
                $validator->errors()->add('training_class_id', 'Kapasitas kelas telah penuh.');
            }
            $codeMatches = RegistrationCode::query()->where('code', $this->string('registration_code'))->where('training_batch_id', $this->integer('training_batch_id'))->exists();
            if (! $codeMatches) {
                $validator->errors()->add('registration_code', 'Kode registrasi tidak berlaku untuk angkatan yang dipilih.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'password.min' => 'Kata sandi minimal :min karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ];
    }
}
