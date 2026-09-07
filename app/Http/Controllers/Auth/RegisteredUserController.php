<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterParticipantRequest;
use App\Models\RegistrationCode;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/register', ['programs' => TrainingProgram::query()->where('is_active', true)->with(['trainingBatches' => fn ($query) => $query->where('status', 'open')->with(['trainingClasses' => fn ($classes) => $classes->where('is_active', true)->select('id', 'training_batch_id', 'name')])->select('id', 'training_program_id', 'name')])->orderBy('name')->get(['id', 'name'])]);
    }

    public function store(RegisterParticipantRequest $request): RedirectResponse
    {
        $photoPath = $request->file('profile_photo')?->store('profile-photos', 'local');
        try {
            DB::transaction(function () use ($request, $photoPath): void {
                $data = $request->validated();
                $code = RegistrationCode::query()->where('code', $data['registration_code'])->lockForUpdate()->firstOrFail();
                abort_if(! $code->is_active || ($code->expires_at && $code->expires_at->isPast()) || ($code->usage_limit && $code->usage_count >= $code->usage_limit), 422, 'Kode registrasi tidak lagi dapat digunakan.');
                $trainingClass = TrainingClass::query()->lockForUpdate()->findOrFail($data['training_class_id']);
                abort_if($trainingClass->enrollments()->whereIn('status', ['pending', 'active'])->count() >= $trainingClass->capacity, 422, 'Kapasitas kelas telah penuh.');
                $user = User::query()->create(['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'], 'password' => $data['password'], 'account_status' => 'pending']);
                $user->assignRole('participant');
                $profile = $user->participantProfile()->make(collect($data)->only(['nik', 'participant_number', 'gender', 'birth_place', 'birth_date', 'address', 'phone'])->all());
                $profile->privacy_accepted_at = now();
                $profile->profile_photo_path = $photoPath;
                $profile->save();
                $enrollment = $user->enrollments()->make(['training_batch_id' => $code->training_batch_id, 'training_class_id' => $data['training_class_id'], 'status' => 'pending', 'enrolled_at' => today()]);
                $enrollment->save();
                $code->increment('usage_count');
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }
            throw $exception;
        }

        return redirect()->route('login')->with('status', 'Pendaftaran berhasil. Akun sedang menunggu verifikasi admin.');
    }
}
