<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'admin-ppkd']) ?? false;
    }

    public function rules(): array
    {
        return ['training_program_id' => ['required', Rule::exists('training_programs', 'id')->whereNull('deleted_at')->where('is_active', true)], 'name' => ['required', 'string', 'max:100', Rule::unique('training_batches')->where('training_program_id', $this->integer('training_program_id'))->whereNull('deleted_at')->ignore($this->route('trainingBatch'))], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'quota' => ['required', 'integer', 'min:1', 'max:1000'], 'status' => ['required', Rule::in(['draft', 'open', 'closed', 'completed'])]];
    }
}
