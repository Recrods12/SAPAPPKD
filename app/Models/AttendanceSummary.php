<?php

namespace App\Models;

use Database\Factories\AttendanceSummaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'training_schedule_id', 'attendance_date', 'morning_status', 'afternoon_status', 'overall_status', 'finalized_at'])]
class AttendanceSummary extends Model
{
    /** @use HasFactory<AttendanceSummaryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['attendance_date' => 'date', 'finalized_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingSchedule(): BelongsTo
    {
        return $this->belongsTo(TrainingSchedule::class);
    }
}
