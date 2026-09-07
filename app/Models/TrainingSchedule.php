<?php

namespace App\Models;

use Database\Factories\TrainingScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['training_class_id', 'attendance_location_id', 'schedule_date', 'start_time', 'end_time', 'morning_open', 'morning_on_time_limit', 'morning_close', 'afternoon_open', 'afternoon_early_limit', 'afternoon_close', 'subject', 'notes', 'status'])]
class TrainingSchedule extends Model
{
    /** @use HasFactory<TrainingScheduleFactory> */
    use HasFactory;

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class);
    }

    public function attendanceLocation(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceSummaries(): HasMany
    {
        return $this->hasMany(AttendanceSummary::class);
    }
}
