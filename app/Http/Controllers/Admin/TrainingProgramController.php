<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrainingProgramRequest;
use App\Http\Requests\Admin\UpdateTrainingProgramRequest;
use App\Models\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $sort = in_array($request->string('sort')->toString(), ['code', 'name', 'duration_days', 'is_active'], true) ? $request->string('sort')->toString() : 'created_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $programs = TrainingProgram::query()
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy($sort, $direction)->orderBy('id')->paginate(10)->withQueryString();

        return Inertia::render('admin/programs/index', compact('programs', 'search', 'status', 'sort', 'direction'));
    }

    public function create(): Response
    {
        return Inertia::render('admin/programs/form', ['program' => null]);
    }

    public function show(TrainingProgram $trainingProgram): Response
    {
        $trainingProgram->loadCount(['trainingBatches']);

        return Inertia::render('admin/master-detail', [
            'title' => $trainingProgram->name,
            'eyebrow' => 'DETAIL PROGRAM',
            'description' => 'Informasi lengkap program pelatihan dan keterkaitannya.',
            'backUrl' => route('admin.programs.index'),
            'editUrl' => route('admin.programs.edit', $trainingProgram),
            'sections' => [[
                'title' => 'Informasi program',
                'fields' => [
                    ['label' => 'Kode', 'value' => $trainingProgram->code],
                    ['label' => 'Nama', 'value' => $trainingProgram->name],
                    ['label' => 'Deskripsi', 'value' => $trainingProgram->description],
                    ['label' => 'Durasi', 'value' => $trainingProgram->duration_days ? $trainingProgram->duration_days.' hari' : null],
                    ['label' => 'Status aktif', 'value' => $trainingProgram->is_active],
                    ['label' => 'Jumlah angkatan', 'value' => $trainingProgram->training_batches_count],
                ],
            ]],
        ]);
    }

    public function store(StoreTrainingProgramRequest $request): RedirectResponse
    {
        TrainingProgram::query()->create($request->validated());

        return redirect()->route('admin.programs.index')->with('status', 'Program pelatihan berhasil ditambahkan.');
    }

    public function edit(TrainingProgram $trainingProgram): Response
    {
        return Inertia::render('admin/programs/form', ['program' => $trainingProgram]);
    }

    public function update(UpdateTrainingProgramRequest $request, TrainingProgram $trainingProgram): RedirectResponse
    {
        $trainingProgram->update($request->validated());

        return redirect()->route('admin.programs.index')->with('status', 'Program pelatihan berhasil diperbarui.');
    }

    public function destroy(TrainingProgram $trainingProgram): RedirectResponse
    {
        abort_if($trainingProgram->trainingBatches()->exists(), 422, 'Program yang memiliki angkatan tidak dapat dihapus.');
        $trainingProgram->delete();

        return back()->with('status', 'Program pelatihan dipindahkan ke arsip.');
    }
}
