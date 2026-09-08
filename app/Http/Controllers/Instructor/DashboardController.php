<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\TrainingSchedule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $classes = $request->user()->instructedClasses()->with('trainingBatch.trainingProgram')->withCount(['enrollments' => fn ($query) => $query->where('status', 'active')])->get();
        $classIds = $classes->pluck('id');
        $todayAttendance = Attendance::query()->whereDate('attendance_date', today())->whereHas('trainingSchedule', fn ($query) => $query->whereIn('training_class_id', $classIds))->whereNull('voided_at');
        $todaySchedules = TrainingSchedule::query()
            ->whereIn('training_class_id', $classIds)
            ->whereDate('schedule_date', today())
            ->where('status', 'active')
            ->with(['trainingClass:id,name,room', 'attendanceLocation:id,name'])
            ->orderBy('start_time')
            ->get();

        return Inertia::render('instructor/dashboard', ['classes' => $classes, 'todaySchedules' => $todaySchedules, 'stats' => ['classes' => $classes->count(), 'participants' => $classes->sum('enrollments_count'), 'presentToday' => (clone $todayAttendance)->distinct()->count('user_id'), 'needsReview' => (clone $todayAttendance)->where('needs_review', true)->count()]]);
    }
}
