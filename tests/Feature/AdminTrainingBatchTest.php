<?php

namespace Tests\Feature;

use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTrainingBatchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_and_update_training_batch(): void
    {
        $admin = $this->admin();
        $program = TrainingProgram::factory()->create();
        $payload = ['training_program_id' => $program->id, 'name' => 'Angkatan 2', 'start_date' => '2026-10-01', 'end_date' => '2026-11-30', 'quota' => 25, 'status' => 'open'];

        $this->actingAs($admin)->post(route('admin.batches.store'), $payload)->assertRedirect(route('admin.batches.index'));
        $batch = TrainingBatch::query()->where('name', 'Angkatan 2')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.batches.update', $batch), [...$payload, 'quota' => 30])->assertRedirect(route('admin.batches.index'));
        $this->assertDatabaseHas('training_batches', ['id' => $batch->id, 'quota' => 30]);
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $program = TrainingProgram::factory()->create();
        $this->actingAs($this->admin())->post(route('admin.batches.store'), ['training_program_id' => $program->id, 'name' => 'Angkatan Salah', 'start_date' => '2026-11-01', 'end_date' => '2026-10-01', 'quota' => 20, 'status' => 'draft'])->assertSessionHasErrors('end_date');
    }

    public function test_participant_cannot_manage_training_batches(): void
    {
        Role::findOrCreate('participant');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');
        $this->actingAs($participant)->get(route('admin.batches.index'))->assertForbidden();
    }

    private function admin(): User
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        return $admin;
    }
}
