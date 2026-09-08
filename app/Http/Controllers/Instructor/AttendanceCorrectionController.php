<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreAttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\InstructorAttendanceCorrection;
use Illuminate\Http\RedirectResponse;

class AttendanceCorrectionController extends Controller
{
    public function store(StoreAttendanceCorrectionRequest $request, Attendance $attendance): RedirectResponse
    {
        InstructorAttendanceCorrection::query()->create(['attendance_id' => $attendance->id, 'instructor_id' => $request->user()->id, ...$request->validated()]);
        $attendance->update(['needs_review' => true]);

        return back()->with('status', 'Permintaan koreksi berhasil dikirim kepada administrator.');
    }
}
