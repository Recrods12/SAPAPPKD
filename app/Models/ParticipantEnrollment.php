<?php

namespace App\Models;

use Database\Factories\ParticipantEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['training_batch_id', 'training_class_id', 'status', 'enrolled_at', 'completed_at'])]
class ParticipantEnrollment extends Model
{
    /** @use HasFactory<ParticipantEnrollmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['enrolled_at' => 'date', 'completed_at' => 'date'];
    }

    public function trainingBatch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class);
    }
}
