<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\ParticipantEnrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected'], 'class_id' => ['nullable', 'integer']]);
        $classes = $request->user()->instructedClasses()->orderBy('name')->get(['training_classes.id', 'name']);
        $classIds = $classes->pluck('id');
        abort_if(($filters['class_id'] ?? null) && ! $classIds->contains((int) $filters['class_id']), 403);
        $participantIds = ParticipantEnrollment::query()->whereIn('training_class_id', isset($filters['class_id']) ? [(int) $filters['class_id']] : $classIds)->where('status', 'active')->pluck('user_id');
        $leaveRequests = LeaveRequest::query()->whereIn('user_id', $participantIds)->with(['user:id,name', 'user.participantProfile:id,user_id,participant_number'])->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->latest()->paginate(20)->withQueryString()->through(fn (LeaveRequest $leaveRequest): array => [...$leaveRequest->toArray(), 'has_document' => filled($leaveRequest->getRawOriginal('document_path'))]);

        return Inertia::render('instructor/leave-requests/index', compact('leaveRequests', 'classes', 'filters'));
    }
}
