<?php

namespace App\Models;

use Database\Factories\AttendanceLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'address', 'latitude', 'longitude', 'radius_meters', 'max_accuracy_meters', 'is_active'])]
class AttendanceLocation extends Model
{
    /** @use HasFactory<AttendanceLocationFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'radius_meters' => 'integer', 'max_accuracy_meters' => 'integer', 'is_active' => 'boolean'];
    }

    public function trainingSchedules(): HasMany
    {
        return $this->hasMany(TrainingSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
