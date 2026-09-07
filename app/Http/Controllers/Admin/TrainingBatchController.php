<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrainingBatchRequest;
use App\Http\Requests\Admin\UpdateTrainingBatchRequest;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingBatchController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $sort = in_array($request->string('sort')->toString(), ['name', 'start_date', 'end_date', 'quota', 'status'], true) ? $request->string('sort')->toString() : 'start_date';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $batches = TrainingBatch::query()->with('trainingProgram:id,code,name')->withCount('trainingClasses')
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhereHas('trainingProgram', fn ($program) => $program->where('name', 'like', "%{$search}%"))))
            ->when($status, fn ($query) => $query->where('status', $status))->orderBy($sort, $direction)->orderBy('id')->paginate(10)->withQueryString();

        return Inertia::render('admin/batches/index', compact('batches', 'search', 'status', 'sort', 'direction'));
    }

    public function create(): Response
    {
        return $this->form();
    }

    public function show(TrainingBatch $trainingBatch): Response
    {
        $trainingBatch->load('trainingProgram:id,code,name')->loadCount(['trainingClasses', 'registrationCodes']);

        return Inertia::render('admin/master-detail', [
            'title' => $trainingBatch->name,
            'eyebrow' => 'DETAIL ANGKATAN',
            'description' => 'Periode, program, serta kapasitas angkatan pelatihan.',
            'backUrl' => route('admin.batches.index'),
            'editUrl' => route('admin.batches.edit', $trainingBatch),
            'sections' => [[
                'title' => 'Informasi angkatan',
                'fields' => [
                    ['label' => 'Program', 'value' => $trainingBatch->trainingProgram?->code.' — '.$trainingBatch->trainingProgram?->name],
                    ['label' => 'Nama angkatan', 'value' => $trainingBatch->name],
                    ['label' => 'Tanggal mulai', 'value' => $trainingBatch->start_date?->format('d-m-Y')],
                    ['label' => 'Tanggal selesai', 'value' => $trainingBatch->end_date?->format('d-m-Y')],
                    ['label' => 'Status', 'value' => $trainingBatch->status],
                    ['label' => 'Jumlah kelas', 'value' => $trainingBatch->training_classes_count],
                    ['label' => 'Kode registrasi', 'value' => $trainingBatch->registration_codes_count],
                ],
            ]],
        ]);
    }

    public function store(StoreTrainingBatchRequest $request): RedirectResponse
    {
        TrainingBatch::query()->create($request->validated());

        return redirect()->route('admin.batches.index')->with('status', 'Angkatan berhasil ditambahkan.');
    }

    public function edit(TrainingBatch $trainingBatch): Response
    {
        return $this->form($trainingBatch);
    }

    public function update(UpdateTrainingBatchRequest $request, TrainingBatch $trainingBatch): RedirectResponse
    {
        $trainingBatch->update($request->validated());

        return redirect()->route('admin.batches.index')->with('status', 'Angkatan berhasil diperbarui.');
    }

    public function destroy(TrainingBatch $trainingBatch): RedirectResponse
    {
        abort_if($trainingBatch->trainingClasses()->exists() || $trainingBatch->registrationCodes()->exists(), 422, 'Angkatan yang sudah digunakan tidak dapat diarsipkan.');
        $trainingBatch->delete();

        return back()->with('status', 'Angkatan berhasil diarsipkan.');
    }

    private function form(?TrainingBatch $trainingBatch = null): Response
    {
        return Inertia::render('admin/batches/form', ['batch' => $trainingBatch, 'programs' => TrainingProgram::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name'])]);
    }
}
