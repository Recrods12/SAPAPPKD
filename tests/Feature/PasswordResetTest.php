<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reset_link_can_be_requested_without_revealing_account_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
        $this->post(route('password.email'), ['email' => 'unknown@example.test'])->assertSessionHas('status');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->post(route('password.store'), ['token' => $token, 'email' => $user->email, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_authenticated_user_must_supply_current_password_to_change_it(): void
    {
        $user = User::factory()->create(['account_status' => 'active', 'password' => 'OldPassword123!']);
        $this->actingAs($user)->put(route('password.update'), ['current_password' => 'wrong', 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
    }
}
