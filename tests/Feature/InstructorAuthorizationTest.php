<?php

namespace Tests\Feature;

use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstructorAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_instructor_can_only_open_an_assigned_class(): void
    {
        Role::findOrCreate('instructor');
        $instructor = User::factory()->create(['account_status' => 'active']);
        $instructor->assignRole('instructor');
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'Angkatan 1', 'start_date' => today(), 'end_date' => today()->addMonth(), 'quota' => 30, 'status' => 'open']);
        $assigned = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);
        $other = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas B', 'capacity' => 20, 'is_active' => true]);
        $assigned->instructors()->attach($instructor);

        $this->actingAs($instructor)->get(route('instructor.dashboard'))->assertOk();
        $this->actingAs($instructor)->get(route('instructor.classes.show', $assigned))->assertOk();
        $this->actingAs($instructor)->get(route('instructor.classes.show', $other))->assertForbidden();
        $this->actingAs($instructor)->get(route('admin.classes.index'))->assertForbidden();
    }
}
