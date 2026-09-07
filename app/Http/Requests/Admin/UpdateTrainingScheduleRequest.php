<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateTrainingScheduleRequest extends StoreTrainingScheduleRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['schedule_date'] = ['required', 'date', Rule::unique('training_schedules')->where('training_class_id', $this->integer('training_class_id'))->ignore($this->route('trainingSchedule'))];

        return $rules;
    }
}
