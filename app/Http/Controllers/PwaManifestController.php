<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class PwaManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = Setting::query()->whereIn('key', ['app_name', 'institution_name'])->pluck('value', 'key');
        $appName = $settings->get('app_name', 'SAPA PPKD');
        $institution = $settings->get('institution_name', 'PPKD Jakarta Barat');

        return response()->json([
            'name' => $appName.' '.$institution,
            'short_name' => $appName,
            'description' => 'Sistem absensi peserta pelatihan '.$institution,
            'id' => '/',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'lang' => 'id-ID',
            'categories' => ['education', 'productivity'],
            'background_color' => '#f8fafc',
            'theme_color' => '#1d4ed8',
            'icons' => [
                ['src' => route('pwa.icon', ['size' => 192], false), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => route('pwa.icon', ['size' => 512], false), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
            'shortcuts' => [[
                'name' => 'Absen Hari Ini',
                'short_name' => 'Absen',
                'url' => route('attendance.create', absolute: false),
                'icons' => [['src' => route('pwa.icon', ['size' => 192], false), 'sizes' => '192x192', 'type' => 'image/png']],
            ]],
        ], headers: ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=300']);
    }
}
