<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Jobs\SendAnnouncementChunk;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('admin/announcements/create');
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $users = User::query()->where('account_status', 'active')->when($data['audience'] !== 'all', fn ($query) => $query->role($data['audience']))->whereKeyNot($request->user()->id);
        $recipientCount = (clone $users)->count();
        $batchId = (string) Str::uuid();
        $users->select('users.id')->chunkById(200, fn ($chunk) => SendAnnouncementChunk::dispatch($chunk->pluck('id')->all(), $data['title'], $data['message'], $batchId)->onQueue('notifications'));
        ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'announcement.queued', 'properties' => ['batch_id' => $batchId, 'audience' => $data['audience'], 'recipients' => $recipientCount, 'title' => $data['title']], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', "Pengumuman dijadwalkan untuk {$recipientCount} pengguna.");
    }
}
