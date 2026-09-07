<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\ActivityLog;
use App\Models\AttendanceLocation;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        abort_unless(request()->user()->hasRole('super-admin'), 403);

        $allSettings = Setting::query()->pluck('value', 'key');
        $settings = $allSettings->except(['logo_path']);
        $requiredSetupKeys = ['institution_address', 'default_location_id', 'mail_from_address', 'logo_path'];

        return Inertia::render('admin/settings/edit', [
            'settings' => $settings,
            'locations' => AttendanceLocation::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'address']),
            'missingSetup' => collect($requiredSetupKeys)->filter(fn (string $key): bool => blank($allSettings->get($key)))->values(),
            'hasLogo' => filled($allSettings->get('logo_path')),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        foreach ($request->safe()->except('logo') as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? '' : (string) $value, 'group' => str_starts_with($key, 'default_') ? 'attendance' : 'general', 'is_public' => in_array($key, ['app_name', 'institution_name', 'institution_address', 'privacy_notice'], true)]);
        }
        if ($request->hasFile('logo')) {
            $oldPath = Setting::query()->where('key', 'logo_path')->value('value');
            $newPath = $request->file('logo')->store('branding', 'local');
            Setting::query()->updateOrCreate(['key' => 'logo_path'], ['value' => $newPath, 'group' => 'branding', 'is_public' => false]);
            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('local')->delete($oldPath);
            }
        }
        ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'settings.updated', 'properties' => ['keys' => array_keys($request->safe()->except('logo')), 'logo_updated' => $request->hasFile('logo')], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', 'Pengaturan berhasil disimpan.');
    }
}
