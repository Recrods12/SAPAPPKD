<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceLocationRequest;
use App\Http\Requests\Admin\UpdateAttendanceLocationRequest;
use App\Models\AttendanceLocation;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceLocationController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $sort = in_array($request->string('sort')->toString(), ['name', 'radius_meters', 'max_accuracy_meters', 'is_active'], true) ? $request->string('sort')->toString() : 'created_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $locations = AttendanceLocation::query()->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%")))
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy($sort, $direction)->orderBy('id')->paginate(10)->withQueryString();

        return Inertia::render('admin/locations/index', compact('locations', 'search', 'status', 'sort', 'direction'));
    }

    public function create(): Response
    {
        return $this->form();
    }

    public function show(AttendanceLocation $attendanceLocation): Response
    {
        $attendanceLocation->loadCount(['trainingSchedules', 'attendances']);

        return Inertia::render('admin/master-detail', [
            'title' => $attendanceLocation->name,
            'eyebrow' => 'DETAIL LOKASI',
            'description' => 'Titik koordinat dan radius sah untuk verifikasi GPS absensi.',
            'backUrl' => route('admin.locations.index'),
            'editUrl' => route('admin.locations.edit', $attendanceLocation),
            'sections' => [[
                'title' => 'Informasi lokasi',
                'fields' => [
                    ['label' => 'Nama', 'value' => $attendanceLocation->name],
                    ['label' => 'Alamat', 'value' => $attendanceLocation->address],
                    ['label' => 'Latitude', 'value' => $attendanceLocation->latitude],
                    ['label' => 'Longitude', 'value' => $attendanceLocation->longitude],
                    ['label' => 'Radius', 'value' => $attendanceLocation->radius_meters.' meter'],
                    ['label' => 'Akurasi GPS maksimum', 'value' => $attendanceLocation->max_accuracy_meters.' meter'],
                    ['label' => 'Status aktif', 'value' => $attendanceLocation->is_active],
                    ['label' => 'Jumlah jadwal', 'value' => $attendanceLocation->training_schedules_count],
                    ['label' => 'Catatan absensi', 'value' => $attendanceLocation->attendances_count],
                ],
            ]],
        ]);
    }

    public function store(StoreAttendanceLocationRequest $request): RedirectResponse
    {
        AttendanceLocation::query()->create($request->validated());

        return redirect()->route('admin.locations.index')->with('status', 'Lokasi absensi berhasil ditambahkan.');
    }

    public function edit(AttendanceLocation $attendanceLocation): Response
    {
        return $this->form($attendanceLocation);
    }

    public function update(UpdateAttendanceLocationRequest $request, AttendanceLocation $attendanceLocation): RedirectResponse
    {
        $attendanceLocation->update($request->validated());

        return redirect()->route('admin.locations.index')->with('status', 'Lokasi absensi berhasil diperbarui.');
    }

    public function destroy(AttendanceLocation $attendanceLocation): RedirectResponse
    {
        abort_if($attendanceLocation->is_active, 422, 'Lokasi aktif tidak dapat diarsipkan. Nonaktifkan terlebih dahulu.');
        $attendanceLocation->delete();

        return back()->with('status', 'Lokasi absensi berhasil diarsipkan.');
    }

    private function form(?AttendanceLocation $attendanceLocation = null): Response
    {
        return Inertia::render('admin/locations/form', [
            'location' => $attendanceLocation,
            'defaults' => Setting::query()->whereIn('key', ['default_radius_meters', 'default_max_accuracy_meters'])->pluck('value', 'key'),
        ]);
    }
}
