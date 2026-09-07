<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PwaIconController extends Controller
{
    public function __invoke(int $size): Response
    {
        abort_unless(in_array($size, [192, 512], true), 404);
        $logoPath = Setting::query()->where('key', 'logo_path')->value('value');

        if (! $logoPath || ! Storage::disk('local')->exists($logoPath)) {
            return response()->file(public_path("icons/sapa-{$size}.png"), ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=300']);
        }

        $source = @imagecreatefromstring((string) Storage::disk('local')->get($logoPath));
        if ($source === false) {
            return response()->file(public_path("icons/sapa-{$size}.png"), ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=300']);
        }

        $canvas = imagecreatetruecolor($size, $size);
        $background = imagecolorallocate($canvas, 29, 78, 216);
        imagefill($canvas, 0, 0, $background);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $available = (int) floor($size * 0.76);
        $scale = min($available / $sourceWidth, $available / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        imagecopyresampled($canvas, $source, (int) (($size - $width) / 2), (int) (($size - $height) / 2), 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        ob_start();
        imagepng($canvas, null, 9);
        $contents = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        return response($contents, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }
}
