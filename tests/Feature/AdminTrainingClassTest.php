<?php

namespace Tests\Feature;

use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTrainingClassTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_class_and_assign_instructor(): void
    {
        $batch = $this->batch();
        Role::findOrCreate('instructor');
        $instructor = User::factory()->create(['account_status' => 'active']);
        $instructor->assignRole('instructor');

        $this->actingAs($this->admin())->post(route('admin.classes.store'), ['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'room' => 'Lab 1', 'capacity' => 20, 'is_active' => true, 'instructor_ids' => [$instructor->id]])->assertRedirect(route('admin.classes.index'));

        $trainingClass = TrainingClass::query()->where('name', 'Kelas A')->firstOrFail();
        $this->assertDatabaseHas('class_instructor', ['training_class_id' => $trainingClass->id, 'user_id' => $instructor->id]);
    }

    public function test_duplicate_class_name_in_same_batch_is_rejected(): void
    {
        $batch = $this->batch();
        TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);

        $this->actingAs($this->admin())->post(route('admin.classes.store'), ['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 25, 'is_active' => true, 'instructor_ids' => []])->assertSessionHasErrors('name');
    }

    public function test_participant_account_cannot_be_assigned_as_instructor(): void
    {
        $batch = $this->batch();
        Role::findOrCreate('participant');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');

        $this->actingAs($this->admin())->post(route('admin.classes.store'), ['training_batch_id' => $batch->id, 'name' => 'Kelas B', 'capacity' => 20, 'is_active' => true, 'instructor_ids' => [$participant->id]])->assertSessionHasErrors('instructor_ids');
    }

    public function test_participant_cannot_manage_classes(): void
    {
        Role::findOrCreate('participant');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');

        $this->actingAs($participant)->get(route('admin.classes.index'))->assertForbidden();
    }

    private function admin(): User
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        return $admin;
    }

    private function batch(): TrainingBatch
    {
        $program = TrainingProgram::factory()->create();

        return TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'Angkatan 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'quota' => 30, 'status' => 'open']);
    }
}
