<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $publicSettings = Schema::hasTable('settings') ? Setting::query()->where('is_public', true)->pluck('value', 'key') : collect();
        $hasLogo = Schema::hasTable('settings') && filled(Setting::query()->where('key', 'logo_path')->value('value'));

        return [
            ...parent::share($request),
            'app' => ['name' => $publicSettings->get('app_name', 'SAPA PPKD'), 'fullName' => $publicSettings->get('institution_name', 'PPKD Jakarta Barat'), 'logoUrl' => $hasLogo ? route('brand.logo') : null, 'privacyNotice' => $publicSettings->get('privacy_notice', 'Foto dan lokasi hanya digunakan untuk memverifikasi kehadiran pelatihan, dibatasi aksesnya, dan disimpan sesuai masa retensi yang ditetapkan PPKD Jakarta Barat.')],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames()->values(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->values(),
                    'unread_notifications_count' => $request->user()->unreadNotifications()->count(),
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
        ];
    }
}
