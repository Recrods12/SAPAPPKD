<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\FraudFlag;
use App\Models\LeaveRequest;
use App\Models\TrainingProgram;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = today();
        $todayAttendances = Attendance::query()->whereDate('attendance_date', $today)->whereNull('voided_at');
        $activeParticipants = User::role('participant')->where('account_status', 'active')->count();
        $morningUsers = (clone $todayAttendances)->where('type', 'morning')->distinct()->count('user_id');
        $afternoonUsers = (clone $todayAttendances)->where('type', 'afternoon')->distinct()->count('user_id');
        $trend = collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = today()->subDays($daysAgo);

            return ['date' => $date->format('d/m'), 'total' => Attendance::query()->whereDate('attendance_date', $date)->whereNull('voided_at')->distinct()->count('user_id')];
        });

        return Inertia::render('admin/dashboard', [
            'stats' => ['participants' => $activeParticipants, 'pending' => User::role('participant')->where('account_status', 'pending')->count(), 'present' => $morningUsers, 'late' => (clone $todayAttendances)->where('status', 'late')->count(), 'leave' => LeaveRequest::query()->where('type', 'leave')->where('status', 'approved')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->count(), 'sick' => LeaveRequest::query()->where('type', 'sick')->where('status', 'approved')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->count(), 'notCheckedIn' => max(0, $activeParticipants - $morningUsers), 'notCheckedOut' => max(0, $morningUsers - $afternoonUsers), 'attendanceRate' => $activeParticipants > 0 ? round(($morningUsers / $activeParticipants) * 100, 1) : 0, 'reviews' => FraudFlag::query()->where('status', 'open')->count(), 'pendingLeaves' => LeaveRequest::query()->where('status', 'pending')->count()],
            'trend' => $trend,
            'programs' => TrainingProgram::query()
                ->select(['id', 'name'])
                ->withCount(['trainingBatches as batches_count'])
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(8)
                ->get(),
        ]);
    }
}
