<?php

namespace App\Models;

use Database\Factories\TrainingProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'description', 'duration_days', 'is_active'])]
class TrainingProgram extends Model
{
    /** @use HasFactory<TrainingProgramFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['duration_days' => 'integer', 'is_active' => 'boolean'];
    }

    public function trainingBatches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class);
    }
}
