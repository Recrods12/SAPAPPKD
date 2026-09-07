<?php

namespace App\Http\Requests\Admin;

use App\Models\TrainingClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'confirmed', Password::defaults()], 'nik' => ['required', 'digits_between:10,20', 'unique:participant_profiles,nik'], 'participant_number' => ['required', 'string', 'max:50', 'unique:participant_profiles,participant_number'], 'gender' => ['required', Rule::in(['male', 'female'])], 'birth_place' => ['required', 'string', 'max:100'], 'birth_date' => ['required', 'date', 'before:today'], 'address' => ['required', 'string', 'max:1000'], 'phone' => ['required', 'regex:/^(?:\+62|62|0)8[0-9]{7,13}$/'], 'training_batch_id' => ['required', 'exists:training_batches,id'], 'training_class_id' => ['nullable', 'exists:training_classes,id'], 'account_status' => ['required', Rule::in(['active', 'inactive'])], 'participant_status' => ['required', Rule::in(['active', 'inactive', 'completed', 'withdrawn'])]];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('training_class_id') && ! TrainingClass::query()->whereKey($this->integer('training_class_id'))->where('training_batch_id', $this->integer('training_batch_id'))->exists()) {
                $validator->errors()->add('training_class_id', 'Kelas tidak termasuk dalam angkatan yang dipilih.');
            }
        }];
    }
}
