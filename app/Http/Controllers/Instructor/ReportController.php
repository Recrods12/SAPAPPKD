<?php

namespace App\Http\Controllers\Instructor;

use App\Exports\AttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $query = $this->query($request, $filters);
        $summary = ['total' => (clone $query)->count(), 'morning' => (clone $query)->where('type', 'morning')->count(), 'afternoon' => (clone $query)->where('type', 'afternoon')->count(), 'late' => (clone $query)->where('status', 'late')->count()];
        $rows = $query->with(['user:id,name', 'user.participantProfile:id,user_id,participant_number', 'trainingSchedule.trainingClass:id,name', 'attendanceLocation:id,name'])->latest('recorded_at')->paginate(25)->withQueryString();

        return Inertia::render('instructor/reports/index', ['rows' => $rows, 'summary' => $summary, 'classes' => $request->user()->instructedClasses()->orderBy('name')->get(['training_classes.id', 'name']), 'filters' => $filters]);
    }

    public function excel(Request $request): BinaryFileResponse
    {
        return Excel::download(new AttendanceReportExport([...$this->filters($request), 'instructor_id' => $request->user()->id]), 'rekap-kelas-'.now()->format('Ymd-His').'.xlsx');
    }

    public function pdf(Request $request): HttpResponse
    {
        $filters = $this->filters($request);
        $rows = $this->query($request, $filters)->with(['user.participantProfile', 'trainingSchedule.trainingClass', 'attendanceLocation'])->limit(2000)->get();
        $identity = ['appName' => Setting::query()->where('key', 'app_name')->value('value') ?: 'SAPA PPKD', 'institutionName' => Setting::query()->where('key', 'institution_name')->value('value') ?: 'PPKD Jakarta Barat'];

        return Pdf::loadView('reports.instructor-attendance', compact('rows', 'filters', 'identity') + ['instructor' => $request->user(), 'generatedAt' => now()])->setPaper('a4', 'landscape')->download('rekap-kelas-'.now()->format('Ymd-His').'.pdf');
    }

    private function filters(Request $request): array
    {
        $filters = $request->validate(['start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'class_id' => ['nullable', 'integer'], 'status' => ['nullable', 'in:on_time,late,present,early_leave,needs_verification,outside_schedule']]);
        $classIds = $request->user()->instructedClasses()->pluck('training_classes.id');
        abort_if(($filters['class_id'] ?? null) && ! $classIds->contains((int) $filters['class_id']), 403);

        return $filters;
    }

    private function query(Request $request, array $filters): Builder
    {
        $classIds = $request->user()->instructedClasses()->pluck('training_classes.id');

        return Attendance::query()->whereNull('voided_at')->whereHas('trainingSchedule', fn ($query) => $query->whereIn('training_class_id', $classIds))->when($filters['start_date'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '>=', $date))->when($filters['end_date'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '<=', $date))->when($filters['class_id'] ?? null, fn ($query, $id) => $query->whereHas('trainingSchedule', fn ($schedule) => $schedule->where('training_class_id', $id)))->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }
}
