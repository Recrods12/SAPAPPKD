<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRegistrationCodeRequest;
use App\Http\Requests\Admin\UpdateRegistrationCodeRequest;
use App\Models\RegistrationCode;
use App\Models\TrainingBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationCodeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:50'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'status' => ['nullable', 'in:active,inactive,expired,exhausted'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? '';
        $codes = RegistrationCode::query()
            ->with('trainingBatch.trainingProgram:id,code,name')
            ->when($search, fn (Builder $query) => $query->where('code', 'like', "%{$search}%"))
            ->when($filters['batch_id'] ?? null, fn (Builder $query, mixed $batchId) => $query->where('training_batch_id', $batchId));
        $this->applyStatusFilter($codes, $status);
        $codes = $codes->latest()->paginate(15)->withQueryString();

        return Inertia::render('admin/registration-codes/index', [
            'codes' => $codes,
            'filters' => ['search' => $search, 'batch_id' => (string) ($filters['batch_id'] ?? ''), 'status' => $status],
            'batches' => TrainingBatch::query()->with('trainingProgram:id,code,name')->orderByDesc('start_date')->get(['id', 'training_program_id', 'name']),
            'stats' => [
                'total' => RegistrationCode::query()->count(),
                'active' => $this->countByStatus('active'),
                'expired' => $this->countByStatus('expired'),
                'exhausted' => $this->countByStatus('exhausted'),
            ],
        ]);
    }

    public function create(): Response
    {
        return $this->form();
    }

    public function store(StoreRegistrationCodeRequest $request): RedirectResponse
    {
        RegistrationCode::query()->create($request->validated() + ['usage_count' => 0]);

        return redirect()->route('admin.registration-codes.index')->with('status', 'Kode registrasi berhasil dibuat.');
    }

    public function edit(RegistrationCode $registrationCode): Response
    {
        return $this->form($registrationCode);
    }

    public function update(UpdateRegistrationCodeRequest $request, RegistrationCode $registrationCode): RedirectResponse
    {
        $registrationCode->update($request->validated());

        return redirect()->route('admin.registration-codes.index')->with('status', 'Kode registrasi berhasil diperbarui.');
    }

    public function destroy(RegistrationCode $registrationCode): RedirectResponse
    {
        abort_if($registrationCode->usage_count > 0, 422, 'Kode yang sudah digunakan tidak dapat dihapus. Nonaktifkan kode tersebut.');
        $registrationCode->delete();

        return back()->with('status', 'Kode registrasi berhasil dihapus.');
    }

    private function form(?RegistrationCode $code = null): Response
    {
        return Inertia::render('admin/registration-codes/form', ['registrationCode' => $code, 'batches' => TrainingBatch::query()->with('trainingProgram:id,code,name')->where('status', 'open')->orderByDesc('start_date')->get(['id', 'training_program_id', 'name'])]);
    }

    private function countByStatus(string $status): int
    {
        $query = RegistrationCode::query();
        $this->applyStatusFilter($query, $status);

        return $query->count();
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'active') {
            $query->where('is_active', true)
                ->where(fn (Builder $builder) => $builder->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->where(fn (Builder $builder) => $builder->whereNull('usage_limit')->orWhereColumn('usage_count', '<', 'usage_limit'));
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
        } elseif ($status === 'exhausted') {
            $query->whereNotNull('usage_limit')->whereColumn('usage_count', '>=', 'usage_limit');
        }
    }
}
