<?php

namespace Database\Seeders;

use App\Models\AttendanceLocation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['super-admin', 'admin-ppkd', 'instructor', 'participant'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $permissions = ['manage participants', 'verify registrations', 'manage training master', 'monitor attendances', 'correct attendances', 'process leave requests', 'download reports', 'review fraud flags', 'send announcements', 'manage users', 'manage settings', 'view activity log', 'view assigned classes', 'submit attendance', 'view own attendance', 'submit leave request', 'manage own profile'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findByName('super-admin')->syncPermissions($permissions);
        Role::findByName('admin-ppkd')->syncPermissions(['manage participants', 'verify registrations', 'manage training master', 'monitor attendances', 'correct attendances', 'process leave requests', 'download reports', 'review fraud flags', 'send announcements']);
        Role::findByName('instructor')->syncPermissions(['view assigned classes']);
        Role::findByName('participant')->syncPermissions(['submit attendance', 'view own attendance', 'submit leave request', 'manage own profile']);
        if (config('initial-admin.username') && config('initial-admin.email') && config('initial-admin.password')) {
            $admin = User::query()
                ->where('username', config('initial-admin.username'))
                ->orWhere('email', config('initial-admin.email'))
                ->first() ?? new User;
            $admin->fill(['name' => config('initial-admin.name'), 'username' => config('initial-admin.username'), 'email' => config('initial-admin.email'), 'password' => config('initial-admin.password'), 'account_status' => 'active', 'verified_at' => now()])->save();
            $admin->syncRoles(['super-admin']);
        }
        foreach ([
            'app_name' => 'SAPA PPKD',
            'institution_name' => 'PPKD Jakarta Barat',
            'institution_address' => '',
            'timezone' => 'Asia/Jakarta',
            'default_location_id' => '',
            'default_radius_meters' => '20',
            'default_max_accuracy_meters' => '35',
            'default_start_time' => '08:00',
            'default_end_time' => '16:00',
            'default_morning_open' => '06:30',
            'default_morning_on_time_limit' => '08:00',
            'default_morning_close' => '09:00',
            'default_afternoon_open' => '15:30',
            'default_afternoon_early_limit' => '16:00',
            'default_afternoon_close' => '18:00',
            'max_photo_size_kb' => '3072',
            'photo_compression_quality' => '82',
            'photo_retention_days' => '365',
            'mail_from_address' => '',
            'privacy_notice' => 'Foto dan lokasi hanya digunakan untuk memverifikasi kehadiran pelatihan, dibatasi aksesnya, dan disimpan sesuai masa retensi yang ditetapkan PPKD Jakarta Barat.',
        ] as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => 'general', 'is_public' => in_array($key, ['app_name', 'institution_name', 'privacy_notice'], true)]);
        }
        AttendanceLocation::query()->firstOrCreate(['name' => 'PPKD Jakarta Barat'], ['address' => 'Jl. Kamal Raya No. 2, Jakarta Barat', 'latitude' => null, 'longitude' => null, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => false]);
    }
}
