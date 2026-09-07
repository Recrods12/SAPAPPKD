<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessLeaveRequestRequest;
use App\Models\ActivityLog;
use App\Models\LeaveRequest;
use App\Notifications\LeaveRequestProcessed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $query = LeaveRequest::query()->with(['user:id,name,email', 'user.participantProfile:id,user_id,participant_number'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $leaveRequests = $query->paginate(15)->withQueryString()->through(fn (LeaveRequest $leaveRequest) => [...$leaveRequest->toArray(), 'has_document' => filled($leaveRequest->getRawOriginal('document_path'))]);

        return Inertia::render('admin/leave-requests/index', ['leaveRequests' => $leaveRequests, 'filters' => $request->only('status')]);
    }

    public function update(ProcessLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->status === 'pending', 422, 'Pengajuan ini sudah diproses.');
        DB::transaction(function () use ($request, $leaveRequest): void {
            $before = $leaveRequest->only(['status', 'processed_by', 'processed_at', 'admin_notes']);
            $leaveRequest->update(['status' => $request->validated('status'), 'processed_by' => $request->user()->id, 'processed_at' => now(), 'admin_notes' => $request->validated('admin_notes')]);
            ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'leave_request.processed', 'subject_type' => LeaveRequest::class, 'subject_id' => $leaveRequest->id, 'properties' => ['before' => $before, 'after' => $leaveRequest->only(['status', 'processed_by', 'processed_at', 'admin_notes'])], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        });
        $leaveRequest->user->notify(new LeaveRequestProcessed($leaveRequest));

        return back()->with('status', 'Pengajuan berhasil diproses.');
    }
}
