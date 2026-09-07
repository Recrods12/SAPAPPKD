<?php

namespace App\Console\Commands;

use App\Models\AttendancePhoto;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('attendance:prune-photos')]
#[Description('Hapus foto absensi yang telah melewati masa retensi')]
class PruneAttendancePhotos extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = max(30, (int) Setting::query()->where('key', 'photo_retention_days')->value('value'));
        $deleted = 0;
        AttendancePhoto::query()->where('uploaded_at', '<', now()->subDays($retentionDays))->chunkById(100, function ($photos) use (&$deleted): void {
            foreach ($photos as $photo) {
                if (Storage::disk($photo->disk)->delete($photo->path)) {
                    $photo->delete();
                    $deleted++;
                }
            }
        });
        $this->info("{$deleted} foto kedaluwarsa dihapus.");

        return self::SUCCESS;
    }
}
