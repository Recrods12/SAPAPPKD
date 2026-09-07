<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\ActivityLog;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $leaveRequests = LeaveRequest::query()->whereBelongsTo($request->user())->latest()->paginate(10)->through(fn (LeaveRequest $leaveRequest) => [...$leaveRequest->toArray(), 'has_document' => filled($leaveRequest->getRawOriginal('document_path'))]);

        return Inertia::render('leave-requests/index', compact('leaveRequests'));
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $overlap = LeaveRequest::query()->whereBelongsTo($request->user())->whereIn('status', ['pending', 'approved'])->whereDate('start_date', '<=', $request->date('end_date'))->whereDate('end_date', '>=', $request->date('start_date'))->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => 'Sudah ada pengajuan aktif pada rentang tanggal tersebut.']);
        }
        $path = $request->file('document')?->store('leave-documents', 'local');
        $leaveRequest = LeaveRequest::query()->create([...$request->safe()->except('document'), 'user_id' => $request->user()->id, 'document_path' => $path, 'status' => 'pending']);
        ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'leave_request.created', 'subject_type' => LeaveRequest::class, 'subject_id' => $leaveRequest->id, 'properties' => ['type' => $leaveRequest->type, 'start_date' => $leaveRequest->start_date], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', 'Pengajuan berhasil dikirim dan menunggu pemeriksaan admin.');
    }

    public function document(Request $request, LeaveRequest $leaveRequest): StreamedResponse
    {
        $authorized = $request->user()->id === $leaveRequest->user_id || $request->user()->hasAnyRole(['super-admin', 'admin-ppkd']);
        abort_unless($authorized, 403);
        abort_unless($leaveRequest->document_path && Storage::disk('local')->exists($leaveRequest->document_path), 404);

        return Storage::disk('local')->response($leaveRequest->document_path, 'dokumen-pengajuan.'.pathinfo($leaveRequest->document_path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }
}
