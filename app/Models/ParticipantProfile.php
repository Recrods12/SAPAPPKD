<?php

namespace App\Models;

use Database\Factories\ParticipantProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nik', 'participant_number', 'gender', 'birth_place', 'birth_date', 'address', 'phone', 'profile_photo_path', 'participant_status', 'privacy_accepted_at'])]
#[Hidden(['profile_photo_path'])]
class ParticipantProfile extends Model
{
    /** @use HasFactory<ParticipantProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'privacy_accepted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
