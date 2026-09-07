<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandLogoController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $path = Setting::query()->where('key', 'logo_path')->value('value');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'logo-sapa-ppkd.'.pathinfo($path, PATHINFO_EXTENSION), ['Cache-Control' => 'public, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }
}
