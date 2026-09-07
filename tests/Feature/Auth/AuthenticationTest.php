<?php

namespace Tests\Feature\Auth;

use App\Models\RegistrationCode;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_admin_is_redirected_to_admin_dashboard_after_login(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create(['username' => 'admin', 'account_status' => 'active', 'password' => 'secret-password']);
        $admin->assignRole('super-admin');

        $this->post('/login', ['login' => 'admin', 'password' => 'secret-password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_username_login_is_case_insensitive(): void
    {
        Role::findOrCreate('participant');
        $participant = User::factory()->create([
            'username' => 'PesertaContoh12',
            'account_status' => 'active',
            'password' => 'secret-password',
        ]);
        $participant->assignRole('participant');

        $this->post('/login', ['login' => 'pesertacontoh12', 'password' => 'secret-password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($participant);
    }

    public function test_pending_account_cannot_remain_authenticated(): void
    {
        $participant = User::factory()->create(['account_status' => 'pending', 'password' => 'secret-password']);

        $this->post('/login', ['login' => $participant->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors(['login' => 'Akun Anda masih menunggu verifikasi admin.']);
        $this->assertGuest();
        $this->assertNull($participant->fresh()->last_login_at);
    }

    public function test_rejected_account_receives_a_specific_login_message(): void
    {
        $participant = User::factory()->create(['account_status' => 'rejected', 'password' => 'secret-password']);

        $this->post('/login', ['login' => $participant->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors(['login' => 'Pendaftaran akun Anda ditolak. Hubungi admin PPKD untuk informasi lebih lanjut.']);
        $this->assertGuest();
    }

    public function test_participant_cannot_access_admin_dashboard(): void
    {
        Role::findOrCreate('participant');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');

        $this->actingAs($participant)->get('/admin')->assertForbidden();
    }

    public function test_valid_registration_creates_pending_participant_and_enrollment(): void
    {
        Role::findOrCreate('participant');
        $program = TrainingProgram::query()->forceCreate(['code' => 'TK', 'name' => 'Teknik Komputer', 'is_active' => true]);
        $batch = TrainingBatch::query()->forceCreate(['training_program_id' => $program->id, 'name' => 'Angkatan 1', 'start_date' => '2026-09-01', 'end_date' => '2026-10-01', 'quota' => 20, 'status' => 'open']);
        $class = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);
        RegistrationCode::query()->forceCreate(['training_batch_id' => $batch->id, 'code' => 'PPKD-VALID', 'is_active' => true, 'usage_limit' => 20]);

        $this->post('/register', ['name' => 'Peserta Baru', 'nik' => '3174000000000001', 'participant_number' => 'P-001', 'gender' => 'male', 'birth_place' => 'Jakarta', 'birth_date' => '2000-01-01', 'address' => 'Jakarta Barat', 'phone' => '081234567890', 'email' => 'peserta@example.test', 'username' => 'peserta-baru', 'training_program_id' => $program->id, 'training_batch_id' => $batch->id, 'training_class_id' => $class->id, 'registration_code' => 'PPKD-VALID', 'password' => '123456', 'password_confirmation' => '123456', 'privacy_consent' => '1'])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['email' => 'peserta@example.test', 'account_status' => 'pending']);
        $this->assertDatabaseHas('participant_profiles', ['nik' => '3174000000000001', 'participant_number' => 'P-001']);
        $this->assertDatabaseHas('participant_enrollments', ['training_batch_id' => $batch->id, 'training_class_id' => $class->id, 'status' => 'pending']);
    }

    public function test_login_is_rate_limited_by_identifier_and_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->post('/login', ['login' => 'target@example.test', 'password' => 'wrong-password'])->assertSessionHasErrors('login');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->post('/login', ['login' => 'target@example.test', 'password' => 'wrong-password'])->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->post('/login', ['login' => 'different@example.test', 'password' => 'wrong-password'])->assertStatus(302);
    }

    public function test_registration_rejects_a_password_shorter_than_six_characters(): void
    {
        $this->post('/register', ['password' => '12345', 'password_confirmation' => '12345'])
            ->assertSessionHasErrors(['password' => 'Kata sandi minimal 6 karakter.']);
        $this->assertSame(0, User::query()->where('account_status', 'pending')->count());
    }

    public function test_registration_is_rate_limited_by_ip_address(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.55'])->post('/register', [])->assertSessionHasErrors('name');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.55'])->post('/register', [])->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.56'])->post('/register', [])->assertStatus(302);
    }
}
