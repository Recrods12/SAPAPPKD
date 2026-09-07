<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewFraudFlagRequest;
use App\Models\ActivityLog;
use App\Models\FraudFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FraudFlagController extends Controller
{
    public function index(Request $request): Response
    {
        $query = FraudFlag::query()->with(['user:id,name', 'user.participantProfile:id,user_id,participant_number', 'attendance:id,attendance_date,type,distance_meters,accuracy_meters'])->latest();
        $query->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')))->when($request->filled('severity'), fn ($builder) => $builder->where('severity', $request->string('severity')))->when($request->filled('reason'), fn ($builder) => $builder->where('reason', $request->string('reason')));

        return Inertia::render('admin/fraud-flags/index', ['flags' => $query->paginate(20)->withQueryString(), 'filters' => $request->only(['status', 'severity', 'reason'])]);
    }

    public function update(ReviewFraudFlagRequest $request, FraudFlag $fraudFlag): RedirectResponse
    {
        $fraudFlag->update(['status' => $request->validated('status'), 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_notes' => $request->validated('review_notes')]);
        if ($fraudFlag->attendance_id) {
            $fraudFlag->attendance()->update(['needs_review' => $fraudFlag->attendance->fraudFlags()->where('status', 'open')->exists()]);
        }
        ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'fraud_flag.reviewed', 'subject_type' => FraudFlag::class, 'subject_id' => $fraudFlag->id, 'properties' => $request->validated(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', 'Peninjauan fraud flag berhasil disimpan.');
    }
}
