<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateParticipantVerificationRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantVerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status', 'pending')->toString();
        $participants = User::role('participant')->with(['participantProfile', 'enrollments.trainingBatch.trainingProgram'])
            ->when(in_array($status, ['pending', 'active', 'rejected'], true), fn ($query) => $query->where('account_status', $status))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('admin/verifications/index', compact('participants', 'status'));
    }

    public function update(UpdateParticipantVerificationRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole('participant'), 404);
        DB::transaction(function () use ($request, $user): void {
            $status = $request->validated('status');
            $user->update(['account_status' => $status, 'verified_at' => now(), 'verified_by' => $request->user()->id]);
            $user->enrollments()->where('status', 'pending')->update(['status' => $status === 'active' ? 'active' : 'rejected']);
            ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'participant.verification_updated', 'subject_type' => User::class, 'subject_id' => $user->id, 'properties' => ['status' => $status], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        });
        $active = $request->validated('status') === 'active';
        $user->notify(new AppNotification($active ? 'Registrasi diterima' : 'Registrasi ditolak', $active ? 'Akun Anda telah aktif dan dapat digunakan untuk absensi.' : 'Registrasi Anda ditolak. Hubungi administrator untuk informasi lebih lanjut.', route('login')));

        return back()->with('status', $request->validated('status') === 'active' ? 'Akun peserta berhasil diaktifkan.' : 'Pendaftaran peserta ditolak.');
    }
}
