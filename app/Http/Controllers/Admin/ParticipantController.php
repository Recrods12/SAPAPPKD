<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreParticipantRequest;
use App\Http\Requests\Admin\UpdateParticipantRequest;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $sort = in_array($request->string('sort')->toString(), ['name', 'email', 'account_status', 'created_at'], true) ? $request->string('sort')->toString() : 'created_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $participants = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'participant'))
            ->with(['participantProfile', 'enrollments.trainingBatch.trainingProgram:id,code,name', 'enrollments.trainingClass:id,name'])
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhereHas('participantProfile', fn ($profile) => $profile->where('participant_number', 'like', "%{$search}%"))))
            ->when($status, fn ($query) => $query->where('account_status', $status))->orderBy($sort, $direction)->orderBy('id')->paginate(15)->withQueryString();

        return Inertia::render('admin/participants/index', compact('participants', 'search', 'status', 'sort', 'direction'));
    }

    public function create(): Response
    {
        return $this->form();
    }

    public function show(User $participant): Response
    {
        $this->ensureParticipant($participant);
        $participant->load(['participantProfile', 'enrollments.trainingBatch.trainingProgram:id,code,name', 'enrollments.trainingClass:id,name']);
        $enrollment = $participant->enrollments->sortByDesc('enrolled_at')->first();

        return Inertia::render('admin/master-detail', [
            'title' => $participant->name,
            'eyebrow' => 'DETAIL PESERTA',
            'description' => 'Identitas, status verifikasi, dan penempatan pelatihan peserta.',
            'backUrl' => route('admin.participants.index'),
            'editUrl' => route('admin.participants.edit', $participant),
            'sections' => [[
                'title' => 'Identitas peserta',
                'fields' => [
                    ['label' => 'Nama', 'value' => $participant->name],
                    ['label' => 'Nomor peserta', 'value' => $participant->participantProfile?->participant_number],
                    ['label' => 'NIK', 'value' => $participant->participantProfile?->nik],
                    ['label' => 'Email', 'value' => $participant->email],
                    ['label' => 'Nomor WhatsApp', 'value' => $participant->participantProfile?->phone],
                    ['label' => 'Jenis kelamin', 'value' => $participant->participantProfile?->gender],
                    ['label' => 'Tempat lahir', 'value' => $participant->participantProfile?->birth_place],
                    ['label' => 'Tanggal lahir', 'value' => $participant->participantProfile?->birth_date?->format('d-m-Y')],
                    ['label' => 'Alamat', 'value' => $participant->participantProfile?->address],
                ],
            ], [
                'title' => 'Status pelatihan',
                'fields' => [
                    ['label' => 'Status akun', 'value' => $participant->account_status],
                    ['label' => 'Status peserta', 'value' => $participant->participantProfile?->participant_status],
                    ['label' => 'Status verifikasi', 'value' => $participant->verified_at ? 'Terverifikasi' : 'Menunggu verifikasi'],
                    ['label' => 'Program', 'value' => $enrollment?->trainingBatch?->trainingProgram?->name],
                    ['label' => 'Angkatan', 'value' => $enrollment?->trainingBatch?->name],
                    ['label' => 'Kelas', 'value' => $enrollment?->trainingClass?->name],
                    ['label' => 'Status pendaftaran', 'value' => $enrollment?->status],
                ],
            ]],
        ]);
    }

    public function store(StoreParticipantRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::query()->create($request->safe()->only(['name', 'email', 'password']) + ['account_status' => $request->validated('account_status'), 'verified_at' => now(), 'verified_by' => $request->user()->id]);
            $user->assignRole('participant');
            $user->participantProfile()->create($request->safe()->only(['nik', 'participant_number', 'gender', 'birth_place', 'birth_date', 'address', 'phone', 'participant_status']) + ['privacy_accepted_at' => now()]);
            $user->enrollments()->create(['training_batch_id' => $request->validated('training_batch_id'), 'training_class_id' => $request->validated('training_class_id'), 'status' => 'active', 'enrolled_at' => now()->toDateString()]);
        });

        return redirect()->route('admin.participants.index')->with('status', 'Peserta berhasil ditambahkan.');
    }

    public function edit(User $participant): Response
    {
        $this->ensureParticipant($participant);

        return $this->form($participant->load([
            'participantProfile',
            'enrollments' => fn ($query) => $query->orderByDesc('enrolled_at')->orderByDesc('id'),
        ]));
    }

    public function update(UpdateParticipantRequest $request, User $participant): RedirectResponse
    {
        $this->ensureParticipant($participant);
        DB::transaction(function () use ($request, $participant): void {
            $account = $request->safe()->only(['name', 'email', 'account_status']);
            if ($request->filled('password')) {
                $account['password'] = $request->validated('password');
            }
            $participant->update($account);
            $participant->participantProfile()->update($request->safe()->only(['nik', 'participant_number', 'gender', 'birth_place', 'birth_date', 'address', 'phone', 'participant_status']));
            $participant->enrollments()->updateOrCreate(['training_batch_id' => $request->validated('training_batch_id')], ['training_class_id' => $request->validated('training_class_id'), 'status' => $request->validated('participant_status'), 'enrolled_at' => now()->toDateString()]);
        });

        return redirect()->route('admin.participants.index')->with('status', 'Data peserta berhasil diperbarui.');
    }

    public function destroy(User $participant): RedirectResponse
    {
        $this->ensureParticipant($participant);
        $participant->update(['account_status' => 'inactive']);
        $participant->participantProfile()->update(['participant_status' => 'inactive']);
        $participant->enrollments()->update(['status' => 'inactive']);

        return back()->with('status', 'Peserta berhasil dinonaktifkan.');
    }

    private function form(?User $participant = null): Response
    {
        return Inertia::render('admin/participants/form', ['participant' => $participant, 'batches' => $this->batches()]);
    }

    private function batches(): Collection
    {
        return TrainingBatch::query()->with(['trainingProgram:id,code,name', 'trainingClasses' => fn ($query) => $query->where('is_active', true)->select(['id', 'training_batch_id', 'name'])])->whereIn('status', ['draft', 'open'])->orderByDesc('start_date')->get(['id', 'training_program_id', 'name']);
    }

    private function ensureParticipant(User $participant): void
    {
        abort_unless($participant->hasRole('participant'), 404);
    }
}
