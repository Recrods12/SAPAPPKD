<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHolidayRequest;
use App\Http\Requests\Admin\UpdateHolidayRequest;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $sort = in_array($request->string('sort')->toString(), ['holiday_date', 'name', 'is_active'], true) ? $request->string('sort')->toString() : 'holiday_date';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $holidays = Holiday::query()->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy($sort, $direction)->orderBy('id')->paginate(12)->withQueryString();

        return Inertia::render('admin/holidays/index', compact('holidays', 'search', 'status', 'sort', 'direction'));
    }

    public function create(): Response
    {
        return Inertia::render('admin/holidays/form', ['holiday' => null]);
    }

    public function show(Holiday $holiday): Response
    {
        return Inertia::render('admin/master-detail', [
            'title' => $holiday->name,
            'eyebrow' => 'DETAIL HARI LIBUR',
            'description' => 'Tanggal yang dikecualikan dari kewajiban absensi pelatihan.',
            'backUrl' => route('admin.holidays.index'),
            'editUrl' => route('admin.holidays.edit', $holiday),
            'sections' => [[
                'title' => 'Informasi hari libur',
                'fields' => [
                    ['label' => 'Nama', 'value' => $holiday->name],
                    ['label' => 'Tanggal', 'value' => Carbon::parse($holiday->holiday_date)->format('d-m-Y')],
                    ['label' => 'Deskripsi', 'value' => $holiday->description],
                ],
            ]],
        ]);
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        Holiday::query()->create($request->validated());

        return redirect()->route('admin.holidays.index')->with('status', 'Hari libur berhasil ditambahkan.');
    }

    public function edit(Holiday $holiday): Response
    {
        return Inertia::render('admin/holidays/form', compact('holiday'));
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($request->validated());

        return redirect()->route('admin.holidays.index')->with('status', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('status', 'Hari libur berhasil dihapus.');
    }
}
