<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTrainingClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        return ['training_batch_id' => ['required', Rule::exists('training_batches', 'id')->whereNull('deleted_at')], 'name' => ['required', 'string', 'max:100', Rule::unique('training_classes')->where('training_batch_id', $this->integer('training_batch_id'))->whereNull('deleted_at')->ignore($this->route('trainingClass'))], 'room' => ['nullable', 'string', 'max:100'], 'capacity' => ['required', 'integer', 'min:1', 'max:500'], 'is_active' => ['required', 'boolean'], 'instructor_ids' => ['array'], 'instructor_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('account_status', 'active')]];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('instructor_ids')) {
                return;
            }
            $ids = array_values(array_unique($this->input('instructor_ids', [])));
            if (User::query()->whereHas('roles', fn ($query) => $query->where('name', 'instructor'))->where('account_status', 'active')->whereIn('id', $ids)->count() !== count($ids)) {
                $validator->errors()->add('instructor_ids', 'Pilihan instruktur tidak valid atau tidak aktif.');
            }
        }];
    }
}
