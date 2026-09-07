<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateRegistrationCodeRequest extends StoreRegistrationCodeRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'] = ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('registration_codes', 'code')->ignore($this->route('registrationCode'))];
        $rules['expires_at'] = ['nullable', 'date'];

        return $rules;
    }
}
