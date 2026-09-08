<?php

namespace App\Models;

use Database\Factories\InstructorAttendanceCorrectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_id', 'instructor_id', 'requested_status', 'reason', 'status', 'admin_notes', 'processed_by', 'processed_at'])]
class InstructorAttendanceCorrection extends Model
{
    /** @use HasFactory<InstructorAttendanceCorrectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
