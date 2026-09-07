<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestProcessed;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveRequestWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Role::findOrCreate('participant');
        Role::findOrCreate('admin-ppkd');
    }

    public function test_participant_can_submit_and_admin_can_approve_leave_request(): void
    {
        Notification::fake();
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        $this->actingAs($participant)->post(route('leave-requests.store'), ['type' => 'sick', 'start_date' => today()->addDay()->toDateString(), 'end_date' => today()->addDays(2)->toDateString(), 'reason' => 'Sedang sakit dan perlu beristirahat.', 'document' => UploadedFile::fake()->image('surat.jpg')])->assertSessionHasNoErrors();
        $leaveRequest = LeaveRequest::query()->firstOrFail();
        Storage::disk('local')->assertExists($leaveRequest->document_path);

        $this->actingAs($admin)->patch(route('admin.leave-requests.update', $leaveRequest), ['status' => 'approved', 'admin_notes' => 'Dokumen telah diperiksa.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'status' => 'approved', 'processed_by' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['event' => 'leave_request.processed', 'subject_id' => $leaveRequest->id]);
        Notification::assertSentTo($participant, LeaveRequestProcessed::class);
    }

    public function test_participant_cannot_read_another_participants_document(): void
    {
        $owner = User::factory()->create(['account_status' => 'active']);
        $owner->assignRole('participant');
        $stranger = User::factory()->create(['account_status' => 'active']);
        $stranger->assignRole('participant');
        Storage::disk('local')->put('leave-documents/private.pdf', 'private');
        $leaveRequest = LeaveRequest::query()->create(['user_id' => $owner->id, 'type' => 'leave', 'start_date' => today()->addDay(), 'end_date' => today()->addDay(), 'reason' => 'Keperluan keluarga yang tidak dapat ditinggalkan.', 'document_path' => 'leave-documents/private.pdf', 'status' => 'pending']);

        $this->actingAs($owner)->get(route('leave-requests.document', $leaveRequest))->assertOk();
        $this->actingAs($stranger)->get(route('leave-requests.document', $leaveRequest))->assertForbidden();
    }

    public function test_admin_can_reject_a_request_with_an_audited_note_and_cannot_process_it_twice(): void
    {
        Notification::fake();
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        $leaveRequest = LeaveRequest::query()->create([
            'user_id' => $participant->id,
            'type' => 'leave',
            'start_date' => today()->addDay(),
            'end_date' => today()->addDay(),
            'reason' => 'Keperluan pribadi yang tidak dapat ditinggalkan.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->patch(route('admin.leave-requests.update', $leaveRequest), [
            'status' => 'rejected',
            'admin_notes' => 'Dokumen pendukung belum memenuhi ketentuan.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'rejected',
            'processed_by' => $admin->id,
            'admin_notes' => 'Dokumen pendukung belum memenuhi ketentuan.',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'leave_request.processed',
            'subject_id' => $leaveRequest->id,
        ]);
        $this->assertNotNull($leaveRequest->fresh()->processed_at);
        Notification::assertSentTo($participant, LeaveRequestProcessed::class);

        $this->actingAs($admin)->patch(route('admin.leave-requests.update', $leaveRequest), [
            'status' => 'approved',
            'admin_notes' => 'Mencoba memproses ulang pengajuan.',
        ])->assertUnprocessable();
        $this->assertSame('rejected', $leaveRequest->fresh()->status);
    }
}
