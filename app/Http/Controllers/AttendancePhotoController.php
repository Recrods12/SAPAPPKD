<?php

namespace App\Http\Controllers;

use App\Models\AttendancePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendancePhotoController extends Controller
{
    public function __invoke(Request $request, AttendancePhoto $attendancePhoto): StreamedResponse
    {
        $attendancePhoto->load('attendance.trainingSchedule.trainingClass.instructors:id');
        $attendance = $attendancePhoto->attendance;
        $authorized = $request->user()->id === $attendance->user_id || $request->user()->hasAnyRole(['super-admin', 'admin-ppkd']) || $attendance->trainingSchedule->trainingClass->instructors->contains($request->user()->id);
        abort_unless($authorized, 403);
        abort_unless(Storage::disk($attendancePhoto->disk)->exists($attendancePhoto->path), 404);

        return Storage::disk($attendancePhoto->disk)->response($attendancePhoto->path, 'foto-absensi.jpg', ['Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }
}
