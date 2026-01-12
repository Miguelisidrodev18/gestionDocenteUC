<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())->first();
        if (! $user && $googleUser->getEmail()) {
            $user = User::where('email', $googleUser->getEmail())->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Usuario Google'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
            ]);
        } elseif (! $user->google_id) {
            $user->google_id = $googleUser->getId();
            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }
            $user->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $this->ensureDocenteProfile($user);

        if (method_exists($user, 'isDocente') && $user->isDocente()) {
            return redirect()->intended(route('teachers.index', absolute: false));
        }

        if (method_exists($user, 'isResponsable') && $user->isResponsable()) {
            return redirect()->intended(route('cursos.checklist', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function ensureDocenteProfile(User $user): void
    {
        if (! method_exists($user, 'isDocente') || ! $user->isDocente()) {
            return;
        }

        $linked = Docente::where('user_id', $user->id)->first();
        if ($linked) {
            return;
        }

        $byEmail = Docente::where('email', $user->email)->first();
        if ($byEmail && (! $byEmail->user_id || $byEmail->user_id === $user->id)) {
            $byEmail->user_id = $user->id;
            $byEmail->save();

            return;
        }

        [$nombre, $apellido] = array_pad(preg_split('/\s+/', trim((string) $user->name), 2), 2, '');
        $nombre = trim((string) $nombre);
        $apellido = trim((string) $apellido);

        $emailForDocente = $user->email;
        if ($byEmail && $byEmail->user_id && $byEmail->user_id !== $user->id) {
            $emailForDocente = $user->email . '.u' . $user->id;
        }

        Docente::create([
            'user_id' => $user->id,
            'nombre' => $nombre !== '' ? $nombre : $user->name,
            'apellido' => $apellido !== '' ? $apellido : 'Pendiente',
            'dni' => sprintf('AUTO-%06d-%s', $user->id, substr((string) now()->format('His'), 0, 6)),
            'email' => $emailForDocente,
            'telefono' => null,
            'especialidad' => 'Pendiente',
            'cv_personal' => null,
            'cv_sunedu' => null,
            'linkedin' => null,
            'estado' => 'activo',
            'cip' => null,
        ]);
    }
}
