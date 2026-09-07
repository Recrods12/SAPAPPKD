<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateParticipantRequest extends StoreParticipantRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $participant = $this->route('participant');
        $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($participant)];
        $rules['password'] = ['nullable', 'confirmed', Password::defaults()];
        $rules['nik'] = ['required', 'digits_between:10,20', Rule::unique('participant_profiles', 'nik')->ignore($participant?->participantProfile?->id)];
        $rules['participant_number'] = ['required', 'string', 'max:50', Rule::unique('participant_profiles', 'participant_number')->ignore($participant?->participantProfile?->id)];

        return $rules;
    }
}
