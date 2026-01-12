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
        $clientId = (string) (config('services.google.client_id') ?: $this->envValue('GOOGLE_CLIENT_ID'));
        $clientSecret = (string) (config('services.google.client_secret') ?: $this->envValue('GOOGLE_CLIENT_SECRET'));

        $redirectUrl = (string) (config('services.google.redirect') ?: $this->envValue('GOOGLE_REDIRECT_URI'));
        if (! $redirectUrl) {
            $redirectUrl = url('/auth/google/callback');
        }
        config([
            'services.google.client_id' => $clientId,
            'services.google.client_secret' => $clientSecret,
            'services.google.redirect' => $redirectUrl,
        ]);

        $driver = Socialite::driver('google')
            ->stateless()
            ->redirectUrl($redirectUrl);

        if ($clientId) {
            $driver->with([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUrl,
                'prompt' => 'select_account consent',
            ]);
        }

        return $driver->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $clientId = (string) (config('services.google.client_id') ?: $this->envValue('GOOGLE_CLIENT_ID'));
        $clientSecret = (string) (config('services.google.client_secret') ?: $this->envValue('GOOGLE_CLIENT_SECRET'));
        $redirectUrl = (string) (config('services.google.redirect') ?: $this->envValue('GOOGLE_REDIRECT_URI'));
        if (! $redirectUrl) {
            $redirectUrl = url('/auth/google/callback');
        }
        config([
            'services.google.client_id' => $clientId,
            'services.google.client_secret' => $clientSecret,
            'services.google.redirect' => $redirectUrl,
        ]);

        $redirectUrl = (string) config('services.google.redirect');
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->redirectUrl($redirectUrl)
            ->user();

        $email = (string) $googleUser->getEmail();
        if ($email === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'No se pudo obtener el correo de Google.',
            ]);
        }

        $allowedDomain = env('GOOGLE_ALLOWED_DOMAIN');
        if ($allowedDomain && ! str_ends_with($email, '@' . $allowedDomain)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Acceso solo para cuentas institucionales.',
            ]);
        }

        $docente = Docente::where('email', $email)->first();
        if (! $docente) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu correo no esta registrado como docente.',
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())->first();
        if (! $user) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Usuario Google'),
                'email' => $email,
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

    private function envValue(string $key): ?string
    {
        $value = env($key);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $value = getenv($key);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            return null;
        }

        foreach (file($envPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_starts_with($line, $key.'=')) {
                continue;
            }
            $raw = substr($line, strlen($key) + 1);
            $raw = trim($raw);
            if ($raw === '') {
                return null;
            }
            if ((str_starts_with($raw, '"') && str_ends_with($raw, '"')) || (str_starts_with($raw, "'") && str_ends_with($raw, "'"))) {
                return substr($raw, 1, -1);
            }

            return $raw;
        }

        return null;
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
