<?php

namespace Tests\Feature;

use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_dashboard_counts_training_batches_using_the_model_relationship(): void
    {
        Role::findOrCreate('admin-ppkd');
        Role::findOrCreate('participant');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        $program = TrainingProgram::factory()->create([
            'name' => 'Teknik Komputer',
            'is_active' => true,
        ]);
        TrainingBatch::factory()->count(2)->create(['training_program_id' => $program->id]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->has('programs', 1)
                ->where('programs.0.id', $program->id)
                ->where('programs.0.name', 'Teknik Komputer')
                ->where('programs.0.batches_count', 2)
                ->has('stats')
                ->has('trend', 7));
    }
}
