<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\DatabaseNotification;
use Ramsey\Uuid\Uuid;

class SendAnnouncementChunk implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 180];

    /** @param array<int, int> $userIds */
    public function __construct(public array $userIds, public string $title, public string $message, public string $batchId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::query()->whereKey($this->userIds)->each(function (User $user): void {
            $notificationId = Uuid::uuid5(Uuid::NAMESPACE_URL, "sapa-ppkd:{$this->batchId}:{$user->id}")->toString();
            if (DatabaseNotification::query()->whereKey($notificationId)->exists()) {
                return;
            }
            $notification = new AppNotification($this->title, $this->message);
            $notification->id = $notificationId;
            $user->notify($notification);
        });
    }
}
