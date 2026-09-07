<?php

namespace Tests\Feature;

use App\Models\AttendanceLocation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_can_update_privacy_notice_and_logo(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole(Role::findOrCreate('super-admin'));
        $location = AttendanceLocation::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->put(route('admin.settings.update'), $this->validSettings($location) + [
            'logo' => UploadedFile::fake()->image('logo.png', 512, 512),
        ])->assertRedirect()->assertSessionHas('status');

        $path = Setting::query()->where('key', 'logo_path')->value('value');
        Storage::disk('local')->assertExists($path);
        $this->assertDatabaseHas('settings', ['key' => 'privacy_notice', 'is_public' => true]);
        $this->get(route('brand.logo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_non_image_logo_is_rejected(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole(Role::findOrCreate('super-admin'));
        $location = AttendanceLocation::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->put(route('admin.settings.update'), $this->validSettings($location) + [
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo');

        $this->assertDatabaseMissing('settings', ['key' => 'logo_path']);
    }

    public function test_settings_page_warns_for_missing_setup_without_exposing_private_logo_path(): void
    {
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole(Role::findOrCreate('super-admin'));
        Setting::query()->create(['key' => 'logo_path', 'value' => 'branding/private-logo.png']);

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertInertia(fn (Assert $page) => $page
            ->component('admin/settings/edit')
            ->where('hasLogo', true)
            ->missing('settings.logo_path')
            ->where('missingSetup', fn ($missing) => $missing->contains('institution_address') && $missing->contains('default_location_id')));
    }

    public function test_configured_operational_defaults_are_supplied_to_location_and_schedule_forms(): void
    {
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole(Role::findOrCreate('super-admin'));
        $location = AttendanceLocation::factory()->create(['is_active' => true]);

        foreach (['default_location_id' => (string) $location->id, 'default_radius_meters' => '25', 'default_max_accuracy_meters' => '40', 'default_start_time' => '07:30', 'default_end_time' => '15:30'] as $key => $value) {
            Setting::query()->create(compact('key', 'value'));
        }

        $this->actingAs($admin)->get(route('admin.locations.create'))->assertInertia(fn (Assert $page) => $page
            ->where('defaults.default_radius_meters', '25')
            ->where('defaults.default_max_accuracy_meters', '40'));

        $this->actingAs($admin)->get(route('admin.schedules.create'))->assertInertia(fn (Assert $page) => $page
            ->where('defaults.default_location_id', (string) $location->id)
            ->where('defaults.default_start_time', '07:30')
            ->where('defaults.default_end_time', '15:30'));
    }

    /** @return array<string, mixed> */
    private function validSettings(AttendanceLocation $location): array
    {
        return [
            'app_name' => 'SAPA PPKD',
            'institution_name' => 'PPKD Jakarta Barat',
            'institution_address' => 'Alamat resmi instansi',
            'timezone' => 'Asia/Jakarta',
            'default_location_id' => $location->id,
            'default_radius_meters' => 20,
            'default_max_accuracy_meters' => 35,
            'default_start_time' => '08:00',
            'default_end_time' => '16:00',
            'default_morning_open' => '06:30',
            'default_morning_on_time_limit' => '08:00',
            'default_morning_close' => '09:00',
            'default_afternoon_open' => '15:30',
            'default_afternoon_early_limit' => '16:00',
            'default_afternoon_close' => '18:00',
            'max_photo_size_kb' => 3072,
            'photo_compression_quality' => 82,
            'photo_retention_days' => 365,
            'mail_from_address' => 'noreply@example.test',
            'privacy_notice' => 'Foto dan lokasi hanya dipakai untuk verifikasi kehadiran peserta pelatihan.',
        ];
    }
}
