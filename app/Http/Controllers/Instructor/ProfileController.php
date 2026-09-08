<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('instructor/profile/edit', ['instructor' => $request->user()->load('instructorProfile')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)], 'phone' => ['required', 'regex:/^(?:\+62|62|0)8[0-9]{7,13}$/'], 'address' => ['nullable', 'string', 'max:1000']]);
        $request->user()->update(collect($validated)->only(['name', 'email'])->all());
        $request->user()->instructorProfile()->updateOrCreate(['user_id' => $request->user()->id], collect($validated)->only(['phone', 'address'])->all());
        ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'instructor.profile.updated', 'subject_type' => $request->user()::class, 'subject_id' => $request->user()->id, 'properties' => ['fields' => array_keys($validated)], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', 'Profil instruktur berhasil diperbarui.');
    }
}
