<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $user = request()->user()->load('participantProfile');

        return Inertia::render('profile/edit', ['participant' => ['name' => $user->name, 'email' => $user->email, 'username' => $user->username, 'profile' => $user->participantProfile]]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->safe()->only(['name', 'email']));
        $request->user()->participantProfile()->update($request->safe()->only(['phone', 'address']));
        ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'profile.updated', 'subject_type' => $request->user()::class, 'subject_id' => $request->user()->id, 'properties' => ['fields' => array_keys($request->validated())], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }
}
