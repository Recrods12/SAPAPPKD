<?php

namespace Tests\Feature;

use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTrainingProgramTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_training_program(): void
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        $this->actingAs($admin)->post(route('admin.programs.store'), ['code' => 'DA', 'name' => 'Data Analyst', 'description' => 'Pelatihan analisis data', 'duration_days' => 45, 'is_active' => 1])->assertRedirect(route('admin.programs.index'))->assertSessionHas('status');

        $this->assertDatabaseHas('training_programs', ['code' => 'DA', 'name' => 'Data Analyst', 'duration_days' => 45]);
    }

    public function test_duplicate_program_code_is_rejected(): void
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        TrainingProgram::factory()->create(['code' => 'TKJ']);

        $this->actingAs($admin)->from(route('admin.programs.create'))->post(route('admin.programs.store'), ['code' => 'TKJ', 'name' => 'Program Lain', 'duration_days' => 30, 'is_active' => 1])->assertRedirect(route('admin.programs.create'))->assertSessionHasErrors('code');

        $this->assertSame(1, TrainingProgram::query()->where('code', 'TKJ')->count());
    }

    public function test_participant_is_forbidden_from_training_program_management(): void
    {
        Role::findOrCreate('participant');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');

        $this->actingAs($participant)->get(route('admin.programs.index'))->assertForbidden();
    }

    public function test_admin_can_update_existing_training_program(): void
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        $program = TrainingProgram::factory()->create(['code' => 'OLD', 'name' => 'Nama Lama']);

        $this->actingAs($admin)->put(route('admin.programs.update', $program), ['code' => 'NEW', 'name' => 'Nama Baru', 'description' => null, 'duration_days' => 60, 'is_active' => 1])->assertRedirect(route('admin.programs.index'));

        $this->assertDatabaseHas('training_programs', ['id' => $program->id, 'code' => 'NEW', 'name' => 'Nama Baru']);
    }

    public function test_admin_program_index_returns_structured_inertia_props(): void
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        TrainingProgram::factory()->count(2)->create();

        $this->actingAs($admin)->get(route('admin.programs.index'))->assertInertia(fn (Assert $page) => $page->component('admin/programs/index')->has('programs.data', 2)->where('search', ''));
    }
}
