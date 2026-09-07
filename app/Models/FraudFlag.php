<?php

namespace App\Models;

use Database\Factories\FraudFlagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_id', 'user_id', 'reason', 'severity', 'metadata', 'status', 'reviewed_by', 'reviewed_at', 'review_notes'])]
class FraudFlag extends Model
{
    /** @use HasFactory<FraudFlagFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['metadata' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
