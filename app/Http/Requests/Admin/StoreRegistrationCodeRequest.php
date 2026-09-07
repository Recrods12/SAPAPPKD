<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        return ['training_batch_id' => ['required', 'exists:training_batches,id'], 'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:registration_codes,code'], 'expires_at' => ['nullable', 'date', 'after:now'], 'usage_limit' => ['nullable', 'integer', 'min:1', 'max:10000'], 'is_active' => ['required', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }
}
