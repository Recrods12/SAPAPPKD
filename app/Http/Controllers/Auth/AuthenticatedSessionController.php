<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $login = $request->string('login')->trim()->toString();
        $identifier = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credential = $identifier === 'username'
            ? User::query()->whereRaw('LOWER(username) = ?', [Str::lower($login)])->value('username') ?? $login
            : $login;

        if (! Auth::attempt([$identifier => $credential, 'password' => $request->string('password')->toString()], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'ID admin/email atau kata sandi tidak sesuai.'])->onlyInput('login');
        }

        if ($request->user()->account_status !== 'active') {
            $message = match ($request->user()->account_status) {
                'pending' => 'Akun Anda masih menunggu verifikasi admin.',
                'rejected' => 'Pendaftaran akun Anda ditolak. Hubungi admin PPKD untuk informasi lebih lanjut.',
                'inactive' => 'Akun Anda sedang dinonaktifkan. Hubungi admin PPKD.',
                default => 'Akun Anda belum dapat digunakan. Hubungi admin PPKD.',
            };

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['login' => $message])->onlyInput('login');
        }

        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);

        $destination = $request->user()->hasAnyRole(['super-admin', 'admin-ppkd']) ? route('admin.dashboard') : ($request->user()->hasRole('instructor') ? route('instructor.dashboard') : route('dashboard'));

        return redirect()->intended($destination);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
