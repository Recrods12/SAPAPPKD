<?php

namespace App\Models;

use Database\Factories\AttendancePhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_id', 'disk', 'path', 'size_bytes', 'mime_type', 'width', 'height', 'sha256', 'source_sha256', 'uploaded_at'])]
class AttendancePhoto extends Model
{
    /** @use HasFactory<AttendancePhotoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
