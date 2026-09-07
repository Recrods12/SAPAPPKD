<?php

namespace Tests\Feature;

use App\Models\RegistrationCode;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRegistrationCodeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_normalized_registration_code(): void
    {
        $batch = $this->batch();
        $this->actingAs($this->admin())->post(route('admin.registration-codes.store'), ['training_batch_id' => $batch->id, 'code' => ' ppkd-a1 ', 'expires_at' => '2026-12-31 23:59', 'usage_limit' => 20, 'is_active' => true])->assertRedirect(route('admin.registration-codes.index'));
        $this->assertDatabaseHas('registration_codes', ['code' => 'PPKD-A1', 'usage_count' => 0]);
    }

    public function test_used_registration_code_cannot_be_deleted(): void
    {
        $code = RegistrationCode::query()->create(['training_batch_id' => $this->batch()->id, 'code' => 'USED', 'usage_count' => 1, 'is_active' => true]);
        $this->actingAs($this->admin())->delete(route('admin.registration-codes.destroy', $code))->assertUnprocessable();
        $this->assertDatabaseHas('registration_codes', ['id' => $code->id]);
    }

    public function test_admin_can_filter_codes_by_operational_status_and_batch(): void
    {
        $batch = $this->batch();
        RegistrationCode::query()->create(['training_batch_id' => $batch->id, 'code' => 'ACTIVE', 'is_active' => true]);
        RegistrationCode::query()->create(['training_batch_id' => $batch->id, 'code' => 'EXPIRED', 'expires_at' => now()->subDay(), 'is_active' => true]);
        RegistrationCode::query()->create(['training_batch_id' => $batch->id, 'code' => 'EXHAUSTED', 'usage_limit' => 1, 'usage_count' => 1, 'is_active' => true]);
        RegistrationCode::query()->create(['training_batch_id' => $batch->id, 'code' => 'DISABLED', 'is_active' => false]);

        $this->actingAs($this->admin())
            ->get(route('admin.registration-codes.index', ['status' => 'active', 'batch_id' => $batch->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/registration-codes/index')
                ->has('codes.data', 1)
                ->where('codes.data.0.code', 'ACTIVE')
                ->where('filters.status', 'active')
                ->where('filters.batch_id', (string) $batch->id)
                ->where('stats.total', 4)
                ->where('stats.active', 1)
                ->where('stats.expired', 1)
                ->where('stats.exhausted', 1));
    }

    public function test_registration_code_filter_rejects_unknown_status(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.registration-codes.index', ['status' => 'unknown']))
            ->assertSessionHasErrors('status');
    }

    private function admin(): User
    {
        Role::findOrCreate('admin-ppkd');
        $user = User::factory()->create(['account_status' => 'active']);
        $user->assignRole('admin-ppkd');

        return $user;
    }

    private function batch(): TrainingBatch
    {
        $program = TrainingProgram::factory()->create();

        return TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'Angkatan 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-31', 'quota' => 20, 'status' => 'open']);
    }
}
