<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AttendancePhotoService
{
    public function store(UploadedFile $photo, string $watermark): array
    {
        $sourceContents = $photo->getContent();
        $image = imagecreatefromstring($sourceContents);
        if ($image === false) {
            throw new RuntimeException('Foto tidak dapat diproses.');
        }
        if (function_exists('exif_read_data') && $photo->getMimeType() === 'image/jpeg') {
            $orientation = @exif_read_data($photo->getRealPath(), 'IFD0')['Orientation'] ?? 1;
            $image = match ($orientation) {
                3 => imagerotate($image, 180, 0),
                6 => imagerotate($image, -90, 0),
                8 => imagerotate($image, 90, 0),
                default => $image,
            };
        }
        $width = imagesx($image);
        $height = imagesy($image);
        $overlay = imagecolorallocatealpha($image, 0, 0, 0, 45);
        $color = imagecolorallocate($image, 255, 255, 255);
        $lines = explode("\n", wordwrap($watermark, max(30, (int) floor($width / 8)), "\n", true));
        $overlayHeight = min($height, (count($lines) * 18) + 20);
        imagefilledrectangle($image, 0, $height - $overlayHeight, $width, $height, $overlay);
        foreach ($lines as $index => $line) {
            imagestring($image, 3, 12, $height - $overlayHeight + 10 + ($index * 18), $line, $color);
        }
        ob_start();
        $quality = max(40, min(95, (int) (Setting::query()->where('key', 'photo_compression_quality')->value('value') ?: 82)));
        imagejpeg($image, null, $quality);
        $contents = ob_get_clean();
        imagedestroy($image);
        if (! is_string($contents)) {
            throw new RuntimeException('Foto gagal dikompresi.');
        }
        $path = 'attendance/'.now()->format('Y/m').'/'.Str::uuid().'.jpg';
        if (! Storage::disk('local')->put($path, $contents)) {
            throw new RuntimeException('Foto gagal disimpan.');
        }

        return ['disk' => 'local', 'path' => $path, 'size_bytes' => strlen($contents), 'mime_type' => 'image/jpeg', 'width' => $width, 'height' => $height, 'sha256' => hash('sha256', $contents), 'source_sha256' => hash('sha256', $sourceContents), 'uploaded_at' => now()];
    }
}
