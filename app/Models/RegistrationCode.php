<?php

namespace App\Models;

use Database\Factories\RegistrationCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['training_batch_id', 'code', 'expires_at', 'usage_limit', 'usage_count', 'is_active'])]
class RegistrationCode extends Model
{
    /** @use HasFactory<RegistrationCodeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'usage_limit' => 'integer', 'usage_count' => 'integer', 'is_active' => 'boolean'];
    }

    public function trainingBatch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class);
    }
}
