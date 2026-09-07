<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrainingClassRequest;
use App\Http\Requests\Admin\UpdateTrainingClassRequest;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainingClassController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $batchId = $request->integer('batch');
        $status = $request->string('status')->toString();
        $sort = in_array($request->string('sort')->toString(), ['name', 'room', 'capacity', 'is_active'], true) ? $request->string('sort')->toString() : 'created_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $classes = TrainingClass::query()->with(['trainingBatch.trainingProgram:id,code,name', 'instructors:id,name'])->withCount('enrollments')
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('room', 'like', "%{$search}%")))
            ->when($batchId, fn ($query) => $query->where('training_batch_id', $batchId))
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy($sort, $direction)->orderBy('id')->paginate(10)->withQueryString();

        return Inertia::render('admin/classes/index', ['classes' => $classes, 'search' => $search, 'batchId' => $batchId, 'status' => $status, 'sort' => $sort, 'direction' => $direction, 'batches' => $this->batches()]);
    }

    public function create(): Response
    {
        return $this->form();
    }

    public function show(TrainingClass $trainingClass): Response
    {
        $trainingClass->load(['trainingBatch.trainingProgram:id,code,name', 'instructors:id,name,email'])->loadCount(['enrollments', 'schedules']);

        return Inertia::render('admin/master-detail', [
            'title' => $trainingClass->name,
            'eyebrow' => 'DETAIL KELAS',
            'description' => 'Informasi kelas, instruktur, kapasitas, dan jumlah peserta.',
            'backUrl' => route('admin.classes.index'),
            'editUrl' => route('admin.classes.edit', $trainingClass),
            'sections' => [[
                'title' => 'Informasi kelas',
                'fields' => [
                    ['label' => 'Program', 'value' => $trainingClass->trainingBatch?->trainingProgram?->name],
                    ['label' => 'Angkatan', 'value' => $trainingClass->trainingBatch?->name],
                    ['label' => 'Nama kelas', 'value' => $trainingClass->name],
                    ['label' => 'Ruangan', 'value' => $trainingClass->room],
                    ['label' => 'Kapasitas', 'value' => $trainingClass->capacity],
                    ['label' => 'Peserta terdaftar', 'value' => $trainingClass->enrollments_count],
                    ['label' => 'Jumlah jadwal', 'value' => $trainingClass->schedules_count],
                    ['label' => 'Status aktif', 'value' => $trainingClass->is_active],
                ],
            ], [
                'title' => 'Instruktur',
                'fields' => $trainingClass->instructors->map(fn (User $instructor): array => ['label' => $instructor->name, 'value' => $instructor->email])->values()->all() ?: [['label' => 'Instruktur', 'value' => null]],
            ]],
        ]);
    }

    public function store(StoreTrainingClassRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $trainingClass = TrainingClass::query()->create($request->safe()->except('instructor_ids'));
            $trainingClass->instructors()->sync($request->validated('instructor_ids', []));
        });

        return redirect()->route('admin.classes.index')->with('status', 'Kelas berhasil ditambahkan.');
    }

    public function edit(TrainingClass $trainingClass): Response
    {
        $trainingClass->load('instructors:id');

        return $this->form($trainingClass);
    }

    public function update(UpdateTrainingClassRequest $request, TrainingClass $trainingClass): RedirectResponse
    {
        DB::transaction(function () use ($request, $trainingClass): void {
            $trainingClass->update($request->safe()->except('instructor_ids'));
            $trainingClass->instructors()->sync($request->validated('instructor_ids', []));
        });

        return redirect()->route('admin.classes.index')->with('status', 'Kelas berhasil diperbarui.');
    }

    public function destroy(TrainingClass $trainingClass): RedirectResponse
    {
        abort_if($trainingClass->enrollments()->exists() || $trainingClass->schedules()->exists(), 422, 'Kelas yang sudah memiliki peserta atau jadwal tidak dapat diarsipkan.');
        $trainingClass->delete();

        return back()->with('status', 'Kelas berhasil diarsipkan.');
    }

    private function form(?TrainingClass $trainingClass = null): Response
    {
        return Inertia::render('admin/classes/form', ['trainingClass' => $trainingClass, 'batches' => $this->batches(), 'instructors' => User::query()->whereHas('roles', fn ($query) => $query->where('name', 'instructor'))->where('account_status', 'active')->orderBy('name')->get(['id', 'name', 'email'])]);
    }

    private function batches(): Collection
    {
        return TrainingBatch::query()->with('trainingProgram:id,code,name')->whereIn('status', ['draft', 'open'])->orderByDesc('start_date')->get(['id', 'training_program_id', 'name']);
    }
}
