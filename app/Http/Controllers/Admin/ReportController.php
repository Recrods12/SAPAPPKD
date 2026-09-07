<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceWorkbookExport;
use App\Exports\FinalizedAttendanceExport;
use App\Exports\FraudFlagReportExport;
use App\Exports\LeaveRequestReportExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\FraudFlag;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\ParticipantAttendanceSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    public function index(Request $request, ParticipantAttendanceSummaryService $summaryService): Response
    {
        $filters = $this->filters($request);
        $query = $this->query($filters);
        $summary = ['total' => (clone $query)->count(), 'onTime' => (clone $query)->whereIn('status', ['on_time', 'present'])->count(), 'late' => (clone $query)->where('status', 'late')->count(), 'incomplete' => (clone $query)->selectRaw('user_id, attendance_date')->groupBy('user_id', 'attendance_date')->havingRaw('COUNT(*) = 1')->get()->count(), 'leave' => LeaveRequest::query()->where('status', 'approved')->where('type', 'leave')->when($filters['start_date'] ?? null, fn ($builder, $date) => $builder->whereDate('end_date', '>=', $date))->when($filters['end_date'] ?? null, fn ($builder, $date) => $builder->whereDate('start_date', '<=', $date))->count(), 'sick' => LeaveRequest::query()->where('status', 'approved')->where('type', 'sick')->when($filters['start_date'] ?? null, fn ($builder, $date) => $builder->whereDate('end_date', '>=', $date))->when($filters['end_date'] ?? null, fn ($builder, $date) => $builder->whereDate('start_date', '<=', $date))->count(), 'fraud' => FraudFlag::query()->where('status', 'open')->when($filters['start_date'] ?? null, fn ($builder, $date) => $builder->whereDate('created_at', '>=', $date))->when($filters['end_date'] ?? null, fn ($builder, $date) => $builder->whereDate('created_at', '<=', $date))->count()];

        $participantQuery = User::role('participant')->with('participantProfile:id,user_id,participant_number')->when($filters['participant_id'] ?? null, fn ($builder, $id) => $builder->whereKey($id))->when($filters['class_id'] ?? null, fn ($builder, $id) => $builder->whereHas('enrollments', fn ($enrollment) => $enrollment->where('training_class_id', $id)))->when($filters['batch_id'] ?? null, fn ($builder, $id) => $builder->whereHas('enrollments', fn ($enrollment) => $enrollment->where('training_batch_id', $id)))->when($filters['program_id'] ?? null, fn ($builder, $id) => $builder->whereHas('enrollments.trainingBatch', fn ($batch) => $batch->where('training_program_id', $id)))->orderBy('name');
        $participantSummaries = $participantQuery->paginate(15, ['*'], 'summary_page')->withQueryString()->through(function (User $user) use ($filters, $summaryService): array {
            $participantSummary = $summaryService->summarize($user, $filters['start_date'] ?? null, $filters['end_date'] ?? null);

            return ['id' => $user->id, 'name' => $user->name, 'participant_number' => $user->participantProfile?->participant_number, ...collect($participantSummary)->except('statuses')->all()];
        });

        return Inertia::render('admin/reports/index', ['rows' => $query->with(['user:id,name', 'user.participantProfile:id,user_id,participant_number', 'trainingSchedule.trainingClass:id,name'])->latest('attendance_date')->paginate(25)->withQueryString(), 'participantSummaries' => $participantSummaries, 'summary' => $summary, 'programs' => TrainingProgram::query()->orderBy('name')->get(['id', 'name']), 'batches' => TrainingBatch::query()->orderBy('name')->get(['id', 'name']), 'classes' => TrainingClass::query()->orderBy('name')->get(['id', 'name']), 'participants' => User::role('participant')->orderBy('name')->get(['id', 'name']), 'instructors' => User::role('instructor')->orderBy('name')->get(['id', 'name']), 'filters' => $filters]);
    }

    public function excel(Request $request): BinaryFileResponse
    {
        $filters = $this->filters($request);

        return Excel::download(new AttendanceWorkbookExport($filters), 'laporan-lengkap-sapa-ppkd-'.now()->format('Ymd-His').'.xlsx');
    }

    public function participants(Request $request, ParticipantAttendanceSummaryService $summaryService): Response
    {
        $filters = $this->filters($request);
        $query = User::role('participant')->with('participantProfile:id,user_id,participant_number')->when($filters['participant_id'] ?? null, fn ($builder, $id) => $builder->whereKey($id))->when($filters['class_id'] ?? null, fn ($builder, $id) => $builder->whereHas('enrollments', fn ($enrollment) => $enrollment->where('training_class_id', $id)))->when($filters['batch_id'] ?? null, fn ($builder, $id) => $builder->whereHas('enrollments', fn ($enrollment) => $enrollment->where('training_batch_id', $id)))->when($filters['program_id'] ?? null, fn ($builder, $id) => $builder->whereHas('enrollments.trainingBatch', fn ($batch) => $batch->where('training_program_id', $id)))->orderBy('name');
        $participants = $query->paginate(25)->withQueryString()->through(function (User $user) use ($filters, $summaryService): array {
            $summary = $summaryService->summarize($user, $filters['start_date'] ?? null, $filters['end_date'] ?? null);

            return ['id' => $user->id, 'name' => $user->name, 'participant_number' => $user->participantProfile?->participant_number, ...collect($summary)->except('statuses')->all()];
        });

        return Inertia::render('admin/reports/participants', ['participants' => $participants, 'programs' => TrainingProgram::query()->orderBy('name')->get(['id', 'name']), 'batches' => TrainingBatch::query()->orderBy('name')->get(['id', 'name']), 'classes' => TrainingClass::query()->orderBy('name')->get(['id', 'name']), 'filters' => $filters]);
    }

    public function pdf(Request $request): HttpResponse
    {
        $filters = $this->filters($request);
        $rows = $this->query($filters)->with(['user.participantProfile', 'trainingSchedule.trainingClass', 'attendanceLocation'])->limit(2000)->get();
        $finalizedRows = (new FinalizedAttendanceExport($filters))->query()->limit(2000)->get();
        $leaveRows = (new LeaveRequestReportExport($filters))->query()->limit(1000)->get();
        $fraudRows = (new FraudFlagReportExport($filters))->query()->limit(1000)->get();
        $reportIdentity = [
            'appName' => Setting::query()->where('key', 'app_name')->value('value') ?: 'SAPA PPKD',
            'institutionName' => Setting::query()->where('key', 'institution_name')->value('value') ?: 'PPKD Jakarta Barat',
            'institutionAddress' => Setting::query()->where('key', 'institution_address')->value('value'),
        ];

        return Pdf::loadView('reports.attendance', compact('rows', 'finalizedRows', 'leaveRows', 'fraudRows', 'filters', 'reportIdentity') + ['generatedAt' => now()])->setPaper('a4', 'landscape')->download('laporan-lengkap-sapa-ppkd-'.now()->format('Ymd-His').'.pdf');
    }

    private function filters(Request $request): array
    {
        $filters = $request->validate(['start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'month' => ['nullable', 'integer', 'between:1,12'], 'year' => ['nullable', 'integer', 'between:2020,2100'], 'program_id' => ['nullable', 'integer', 'exists:training_programs,id'], 'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'], 'class_id' => ['nullable', 'integer', 'exists:training_classes,id'], 'participant_id' => ['nullable', 'integer', 'exists:users,id'], 'instructor_id' => ['nullable', 'integer', 'exists:users,id'], 'status' => ['nullable', Rule::in(['on_time', 'late', 'present', 'early_leave', 'needs_verification', 'outside_schedule'])], 'final_status' => ['nullable', Rule::in(['present', 'late', 'leave', 'sick', 'absent', 'incomplete', 'holiday'])]]);
        if (($filters['month'] ?? null) && ($filters['year'] ?? null) && ! ($filters['start_date'] ?? null) && ! ($filters['end_date'] ?? null)) {
            $month = CarbonImmutable::create((int) $filters['year'], (int) $filters['month'], 1, 0, 0, 0, config('app.timezone'));
            $filters['start_date'] = $month->startOfMonth()->toDateString();
            $filters['end_date'] = $month->endOfMonth()->toDateString();
        }

        return $filters;
    }

    private function query(array $filters): Builder
    {
        return Attendance::query()->whereNull('voided_at')->when($filters['start_date'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '>=', $date))->when($filters['end_date'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '<=', $date))->when($filters['program_id'] ?? null, fn ($query, $id) => $query->whereHas('trainingSchedule.trainingClass.trainingBatch', fn ($batch) => $batch->where('training_program_id', $id)))->when($filters['batch_id'] ?? null, fn ($query, $id) => $query->whereHas('trainingSchedule.trainingClass', fn ($class) => $class->where('training_batch_id', $id)))->when($filters['class_id'] ?? null, fn ($query, $id) => $query->whereHas('trainingSchedule', fn ($schedule) => $schedule->where('training_class_id', $id)))->when($filters['participant_id'] ?? null, fn ($query, $id) => $query->where('user_id', $id))->when($filters['instructor_id'] ?? null, fn ($query, $id) => $query->whereHas('trainingSchedule.trainingClass.instructors', fn ($instructor) => $instructor->whereKey($id)))->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }
}
