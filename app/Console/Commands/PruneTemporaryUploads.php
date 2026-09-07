<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

#[Signature('maintenance:prune-temporary-uploads')]
#[Description('Hapus file upload sementara yang tidak lagi digunakan')]
class PruneTemporaryUploads extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = now()->subDay()->getTimestamp();
        $deleted = 0;
        foreach (Storage::disk('local')->allFiles('temporary-uploads') as $path) {
            if (Storage::disk('local')->lastModified($path) < $threshold && Storage::disk('local')->delete($path)) {
                $deleted++;
            }
        }
        Log::info('Pembersihan upload sementara selesai.', ['deleted' => $deleted]);
        $this->info("{$deleted} file sementara dihapus.");

        return self::SUCCESS;
    }
}
