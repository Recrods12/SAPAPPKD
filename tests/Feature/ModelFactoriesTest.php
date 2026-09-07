<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceLocation;
use App\Models\AttendancePhoto;
use App\Models\AttendanceSummary;
use App\Models\DeviceToken;
use App\Models\FraudFlag;
use App\Models\Holiday;
use App\Models\InstructorProfile;
use App\Models\LeaveRequest;
use App\Models\ParticipantEnrollment;
use App\Models\ParticipantProfile;
use App\Models\RegistrationCode;
use App\Models\Setting;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\TrainingSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ModelFactoriesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_all_domain_factories_create_valid_records(): void
    {
        $models = [TrainingProgram::class, TrainingBatch::class, TrainingClass::class, AttendanceLocation::class, TrainingSchedule::class, ParticipantProfile::class, InstructorProfile::class, ParticipantEnrollment::class, RegistrationCode::class, Attendance::class, AttendancePhoto::class, AttendanceSummary::class, LeaveRequest::class, Holiday::class, DeviceToken::class, FraudFlag::class, AttendanceCorrection::class, Setting::class, ActivityLog::class];

        foreach ($models as $modelClass) {
            /** @var Model $model */
            $model = $modelClass::factory()->create();
            $this->assertTrue($model->exists, "Factory {$modelClass} gagal membuat record.");
        }
    }
}
