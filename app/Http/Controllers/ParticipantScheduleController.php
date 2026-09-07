<?php

namespace App\Http\Controllers;

use App\Models\TrainingSchedule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantScheduleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $classId = $request->user()->enrollments()->where('status', 'active')->latest()->value('training_class_id');
        $schedules = $classId ? TrainingSchedule::query()->with('attendanceLocation:id,name,address')->where('training_class_id', $classId)->where('status', 'active')->orderBy('schedule_date')->paginate(20) : null;

        return Inertia::render('schedule/index', ['schedules' => $schedules]);
    }
}
