<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestProcessed extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Pengajuan '.($this->leaveRequest->status === 'approved' ? 'disetujui' : 'ditolak'), 'leave_request_id' => $this->leaveRequest->id, 'status' => $this->leaveRequest->status, 'admin_notes' => $this->leaveRequest->admin_notes];
    }
}
