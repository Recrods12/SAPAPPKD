<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstructorRequest;
use App\Http\Requests\Admin\UpdateInstructorRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InstructorController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $sort = in_array($request->string('sort')->toString(), ['name', 'email', 'account_status', 'created_at'], true) ? $request->string('sort')->toString() : 'created_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $instructors = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'instructor'))->with(['instructorProfile', 'instructedClasses:id,name'])->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))->when($status, fn ($query) => $query->where('account_status', $status))->orderBy($sort, $direction)->orderBy('id')->paginate(10)->withQueryString();

        return Inertia::render('admin/instructors/index', compact('instructors', 'search', 'status', 'sort', 'direction'));
    }

    public function create(): Response
    {
        return Inertia::render('admin/instructors/form', ['instructor' => null]);
    }

    public function show(User $instructor): Response
    {
        abort_unless($instructor->hasRole('instructor'), 404);
        $instructor->load(['instructorProfile', 'instructedClasses.trainingBatch.trainingProgram:id,code,name']);

        return Inertia::render('admin/master-detail', [
            'title' => $instructor->name,
            'eyebrow' => 'DETAIL INSTRUKTUR',
            'description' => 'Profil instruktur serta kelas pelatihan yang ditangani.',
            'backUrl' => route('admin.instructors.index'),
            'editUrl' => route('admin.instructors.edit', $instructor),
            'sections' => [[
                'title' => 'Profil instruktur',
                'fields' => [
                    ['label' => 'Nama', 'value' => $instructor->name],
                    ['label' => 'Email', 'value' => $instructor->email],
                    ['label' => 'Nomor pegawai', 'value' => $instructor->instructorProfile?->employee_number],
                    ['label' => 'Nomor WhatsApp', 'value' => $instructor->instructorProfile?->phone],
                    ['label' => 'Alamat', 'value' => $instructor->instructorProfile?->address],
                    ['label' => 'Status akun', 'value' => $instructor->account_status],
                    ['label' => 'Status instruktur', 'value' => $instructor->instructorProfile?->is_active ?? false],
                ],
            ], [
                'title' => 'Kelas yang ditangani',
                'fields' => $instructor->instructedClasses->map(fn ($class): array => [
                    'label' => $class->name,
                    'value' => $class->trainingBatch?->trainingProgram?->name.' — '.$class->trainingBatch?->name,
                ])->values()->all() ?: [['label' => 'Kelas', 'value' => null]],
            ]],
        ]);
    }

    public function store(StoreInstructorRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::query()->create($request->safe()->only(['name', 'email', 'password']) + ['account_status' => 'active', 'verified_at' => now()]);
            $user->assignRole('instructor');
            $user->instructorProfile()->create($request->safe()->only(['employee_number', 'phone', 'address', 'is_active']));
        });

        return redirect()->route('admin.instructors.index')->with('status', 'Instruktur berhasil ditambahkan.');
    }

    public function edit(User $instructor): Response
    {
        abort_unless($instructor->hasRole('instructor'), 404);

        return Inertia::render('admin/instructors/form', ['instructor' => $instructor->load('instructorProfile')]);
    }

    public function update(UpdateInstructorRequest $request, User $instructor): RedirectResponse
    {
        abort_unless($instructor->hasRole('instructor'), 404);
        DB::transaction(function () use ($request, $instructor): void {
            $account = $request->safe()->only(['name', 'email', 'account_status']);
            if ($request->filled('password')) {
                $account['password'] = $request->validated('password');
            }
            $instructor->update($account);
            $instructor->instructorProfile()->updateOrCreate([], $request->safe()->only(['employee_number', 'phone', 'address', 'is_active']));
        });

        return redirect()->route('admin.instructors.index')->with('status', 'Instruktur berhasil diperbarui.');
    }

    public function destroy(User $instructor): RedirectResponse
    {
        abort_unless($instructor->hasRole('instructor'), 404);
        abort_if($instructor->instructedClasses()->exists(), 422, 'Instruktur yang masih menangani kelas tidak dapat dinonaktifkan.');
        $instructor->update(['account_status' => 'inactive']);
        $instructor->instructorProfile()->update(['is_active' => false]);

        return back()->with('status', 'Instruktur berhasil dinonaktifkan.');
    }
}
