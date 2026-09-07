<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\TrainingClass;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    public function show(Request $request, TrainingClass $trainingClass): Response
    {
        abort_unless($request->user()->instructedClasses()->whereKey($trainingClass)->exists(), 403);
        $trainingClass->load('trainingBatch.trainingProgram');
        $participants = $trainingClass->enrollments()->where('status', 'active')->with(['user:id,name,email', 'user.participantProfile:id,user_id,participant_number,phone'])->paginate(25);

        return Inertia::render('instructor/classes/show', ['trainingClass' => $trainingClass, 'participants' => $participants]);
    }
}
