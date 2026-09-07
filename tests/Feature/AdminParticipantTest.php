<?php

namespace Tests\Feature;

use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminParticipantTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_participant_and_place_them_in_class(): void
    {
        Role::findOrCreate('participant');
        [$batch, $class] = $this->trainingData();
        $payload = ['name' => 'Peserta Admin', 'email' => 'peserta.admin@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'nik' => '3174000000000099', 'participant_number' => 'P-099', 'gender' => 'male', 'birth_place' => 'Jakarta', 'birth_date' => '2000-01-01', 'address' => 'Jakarta Barat', 'phone' => '08123456789', 'training_batch_id' => $batch->id, 'training_class_id' => $class->id, 'account_status' => 'active', 'participant_status' => 'active'];

        $this->actingAs($this->admin())->post(route('admin.participants.store'), $payload)->assertRedirect(route('admin.participants.index'));
        $participant = User::query()->where('email', $payload['email'])->firstOrFail();
        $this->assertTrue($participant->hasRole('participant'));
        $this->assertDatabaseHas('participant_enrollments', ['user_id' => $participant->id, 'training_class_id' => $class->id]);
    }

    public function test_class_must_belong_to_selected_batch(): void
    {
        Role::findOrCreate('participant');
        [$batch] = $this->trainingData();
        [, $otherClass] = $this->trainingData('Lain');
        $payload = ['name' => 'Peserta Salah', 'email' => 'salah@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'nik' => '3174000000000088', 'participant_number' => 'P-088', 'gender' => 'female', 'birth_place' => 'Jakarta', 'birth_date' => '2001-01-01', 'address' => 'Jakarta', 'phone' => '0812', 'training_batch_id' => $batch->id, 'training_class_id' => $otherClass->id, 'account_status' => 'active', 'participant_status' => 'active'];

        $this->actingAs($this->admin())->post(route('admin.participants.store'), $payload)->assertSessionHasErrors('training_class_id');
    }

    public function test_edit_form_uses_the_participants_latest_enrollment(): void
    {
        Role::findOrCreate('participant');
        [$oldBatch] = $this->trainingData('Lama');
        [$latestBatch, $latestClass] = $this->trainingData('Terbaru');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');
        $participant->participantProfile()->create([
            'nik' => '3174000000000077',
            'participant_number' => 'P-077',
            'gender' => 'male',
            'birth_place' => 'Jakarta',
            'birth_date' => '2000-01-01',
            'address' => 'Jakarta Barat',
            'phone' => '08123456789',
            'participant_status' => 'active',
            'privacy_accepted_at' => now(),
        ]);
        $participant->enrollments()->create(['training_batch_id' => $oldBatch->id, 'status' => 'active', 'enrolled_at' => '2026-08-01']);
        $participant->enrollments()->create(['training_batch_id' => $latestBatch->id, 'training_class_id' => $latestClass->id, 'status' => 'active', 'enrolled_at' => '2026-09-01']);

        $this->actingAs($this->admin())->get(route('admin.participants.edit', $participant))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/participants/form')
                ->where('participant.enrollments.0.training_batch_id', $latestBatch->id)
                ->where('participant.enrollments.0.training_class_id', $latestClass->id));
    }

    private function admin(): User
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        return $admin;
    }

    private function trainingData(string $suffix = ''): array
    {
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'Angkatan '.$suffix, 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'quota' => 30, 'status' => 'open']);
        $class = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas '.$suffix, 'capacity' => 20, 'is_active' => true]);

        return [$batch, $class];
    }
}
