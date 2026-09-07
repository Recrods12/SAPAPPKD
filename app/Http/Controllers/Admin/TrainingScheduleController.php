<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrainingScheduleRequest;
use App\Http\Requests\Admin\UpdateTrainingScheduleRequest;
use App\Models\AttendanceLocation;
use App\Models\Setting;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TrainingScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $date = $request->string('date')->toString();
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $sort = in_array($request->string('sort')->toString(), ['schedule_date', 'start_time', 'end_time', 'subject', 'status'], true) ? $request->string('sort')->toString() : 'schedule_date';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $schedules = TrainingSchedule::query()->with(['trainingClass.trainingBatch.trainingProgram:id,code,name', 'attendanceLocation:id,name'])
            ->when($date, fn ($query) => $query->whereDate('schedule_date', $date))
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('subject', 'like', "%{$search}%")->orWhereHas('trainingClass', fn ($class) => $class->where('name', 'like', "%{$search}%"))))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $direction)->orderBy('id')->paginate(15)->withQueryString();

        return Inertia::render('admin/schedules/index', compact('schedules', 'date', 'search', 'status', 'sort', 'direction'));
    }

    public function create(): Response
    {
        return $this->form();
    }

    public function show(TrainingSchedule $trainingSchedule): Response
    {
        $trainingSchedule->load(['trainingClass.trainingBatch.trainingProgram:id,code,name', 'attendanceLocation:id,name,address'])->loadCount('attendances');

        return Inertia::render('admin/master-detail', [
            'title' => 'Jadwal '.Carbon::parse($trainingSchedule->schedule_date)->format('d-m-Y'),
            'eyebrow' => 'DETAIL JADWAL',
            'description' => 'Waktu absensi pagi dan sore beserta lokasi yang berlaku.',
            'backUrl' => route('admin.schedules.index'),
            'editUrl' => route('admin.schedules.edit', $trainingSchedule),
            'sections' => [[
                'title' => 'Informasi jadwal',
                'fields' => [
                    ['label' => 'Program', 'value' => $trainingSchedule->trainingClass?->trainingBatch?->trainingProgram?->name],
                    ['label' => 'Angkatan', 'value' => $trainingSchedule->trainingClass?->trainingBatch?->name],
                    ['label' => 'Kelas', 'value' => $trainingSchedule->trainingClass?->name],
                    ['label' => 'Tanggal', 'value' => Carbon::parse($trainingSchedule->schedule_date)->format('d-m-Y')],
                    ['label' => 'Batas pagi', 'value' => $trainingSchedule->morning_open.'–'.$trainingSchedule->morning_close],
                    ['label' => 'Batas sore', 'value' => $trainingSchedule->afternoon_open.'–'.$trainingSchedule->afternoon_close],
                    ['label' => 'Batas tepat waktu pagi', 'value' => $trainingSchedule->morning_on_time_limit],
                    ['label' => 'Batas normal sore', 'value' => $trainingSchedule->afternoon_early_limit],
                    ['label' => 'Lokasi', 'value' => $trainingSchedule->attendanceLocation?->name],
                    ['label' => 'Catatan absensi', 'value' => $trainingSchedule->attendances_count],
                ],
            ]],
        ]);
    }

    public function store(StoreTrainingScheduleRequest $request): RedirectResponse
    {
        TrainingSchedule::query()->create($request->validated());

        return redirect()->route('admin.schedules.index')->with('status', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(TrainingSchedule $trainingSchedule): Response
    {
        return $this->form($trainingSchedule);
    }

    public function update(UpdateTrainingScheduleRequest $request, TrainingSchedule $trainingSchedule): RedirectResponse
    {
        $trainingSchedule->update($request->validated());

        return redirect()->route('admin.schedules.index')->with('status', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(TrainingSchedule $trainingSchedule): RedirectResponse
    {
        abort_if($trainingSchedule->attendances()->exists(), 422, 'Jadwal yang sudah memiliki absensi tidak dapat dihapus.');
        $trainingSchedule->delete();

        return back()->with('status', 'Jadwal berhasil dihapus.');
    }

    private function form(?TrainingSchedule $schedule = null): Response
    {
        return Inertia::render('admin/schedules/form', [
            'schedule' => $schedule,
            'classes' => TrainingClass::query()->with('trainingBatch.trainingProgram:id,code,name')->where('is_active', true)->orderBy('name')->get(),
            'locations' => AttendanceLocation::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'defaults' => Setting::query()->whereIn('key', ['default_location_id', 'default_start_time', 'default_end_time', 'default_morning_open', 'default_morning_on_time_limit', 'default_morning_close', 'default_afternoon_open', 'default_afternoon_early_limit', 'default_afternoon_close'])->pluck('value', 'key'),
        ]);
    }
}
