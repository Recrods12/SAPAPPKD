<?php

namespace App\Models;

use Database\Factories\TrainingBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['training_program_id', 'name', 'start_date', 'end_date', 'quota', 'status'])]
class TrainingBatch extends Model
{
    /** @use HasFactory<TrainingBatchFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'quota' => 'integer'];
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function trainingClasses(): HasMany
    {
        return $this->hasMany(TrainingClass::class);
    }

    public function registrationCodes(): HasMany
    {
        return $this->hasMany(RegistrationCode::class);
    }
}
