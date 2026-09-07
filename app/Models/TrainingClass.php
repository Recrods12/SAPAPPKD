<?php

namespace App\Models;

use Database\Factories\TrainingClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['training_batch_id', 'name', 'room', 'capacity', 'is_active'])]
class TrainingClass extends Model
{
    /** @use HasFactory<TrainingClassFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'is_active' => 'boolean'];
    }

    public function trainingBatch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_instructor')->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ParticipantEnrollment::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TrainingSchedule::class);
    }
}
