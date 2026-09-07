<?php

namespace Tests\Feature;

use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantVerificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_activate_pending_participant_and_enrollment(): void
    {
        Role::findOrCreate('admin-ppkd');
        Role::findOrCreate('participant');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        $participant = User::factory()->create(['account_status' => 'pending']);
        $participant->assignRole('participant');
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->forceCreate(['training_program_id' => $program->id, 'name' => 'Angkatan 1', 'start_date' => '2026-09-01', 'end_date' => '2026-10-01', 'quota' => 20, 'status' => 'open']);
        $participant->enrollments()->create(['training_batch_id' => $batch->id, 'status' => 'pending', 'enrolled_at' => '2026-09-01']);

        $this->actingAs($admin)->patch(route('admin.verifications.update', $participant), ['status' => 'active'])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('users', ['id' => $participant->id, 'account_status' => 'active', 'verified_by' => $admin->id]);
        $this->assertDatabaseHas('participant_enrollments', ['user_id' => $participant->id, 'status' => 'active']);
    }

    public function test_verification_rejects_unsupported_status(): void
    {
        Role::findOrCreate('admin-ppkd');
        Role::findOrCreate('participant');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        $participant = User::factory()->create(['account_status' => 'pending']);
        $participant->assignRole('participant');

        $this->actingAs($admin)->patch(route('admin.verifications.update', $participant), ['status' => 'super-admin'])->assertSessionHasErrors('status');

        $this->assertSame('pending', $participant->fresh()->account_status);
    }
}
