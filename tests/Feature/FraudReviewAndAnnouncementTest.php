<?php

namespace Tests\Feature;

use App\Jobs\SendAnnouncementChunk;
use App\Models\FraudFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FraudReviewAndAnnouncementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_review_flag_and_send_announcement(): void
    {
        Queue::fake([SendAnnouncementChunk::class]);
        Role::findOrCreate('admin-ppkd');
        Role::findOrCreate('participant');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');
        $flag = FraudFlag::query()->create(['user_id' => $participant->id, 'reason' => 'shared_device', 'severity' => 'warning', 'status' => 'open']);

        $this->actingAs($admin)->patch(route('admin.fraud-flags.update', $flag), ['status' => 'reviewed', 'review_notes' => 'Perangkat digunakan bersama dengan alasan yang valid.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fraud_flags', ['id' => $flag->id, 'status' => 'reviewed', 'reviewed_by' => $admin->id]);
        $this->actingAs($admin)->post(route('admin.announcements.store'), ['title' => 'Perubahan jadwal', 'message' => 'Jadwal pelatihan besok dimulai pada pukul delapan.', 'audience' => 'participant'])->assertSessionHasNoErrors();
        Queue::assertPushed(SendAnnouncementChunk::class, fn (SendAnnouncementChunk $job) => in_array($participant->id, $job->userIds, true) && $job->queue === 'notifications');
        $this->assertDatabaseHas('activity_logs', ['event' => 'announcement.queued', 'user_id' => $admin->id]);
    }

    public function test_announcement_job_is_idempotent(): void
    {
        $participant = User::factory()->create(['account_status' => 'active']);
        $job = new SendAnnouncementChunk([$participant->id], 'Informasi', 'Pesan pengumuman pelatihan.', 'batch-test-1');

        $job->handle();
        $job->handle();

        $this->assertSame(1, $participant->notifications()->count());
    }
}
