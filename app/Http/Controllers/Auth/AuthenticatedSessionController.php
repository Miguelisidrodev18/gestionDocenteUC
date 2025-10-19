<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Ensure Docente exists for docente role
        if ($user && $user->role === 'docente') {
            $linked = Docente::where('user_id', $user->id)->first();
            if (! $linked) {
                // Try link by email if exists
                $byEmail = Docente::where('email', $user->email)->first();
                if ($byEmail && (! $byEmail->user_id || $byEmail->user_id === $user->id)) {
                    $byEmail->user_id = $user->id;
                    $byEmail->save();
                } else {
                    // Create a minimal docente profile
                    [$nombre, $apellido] = array_pad(preg_split('/\s+/', trim((string) $user->name), 2), 2, '');
                    $nombre = trim((string) $nombre);
                    $apellido = trim((string) $apellido);

                    // Ensure unique email in docentes table
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
        }

        // Redirect based on role
        if ($user && $user->role === 'docente') {
            return redirect()->intended(route('docentes.index', absolute: false));
        }

        if ($user && $user->role === 'responsable') {
            // Responsable users see checklist
            return redirect()->intended(route('cursos.checklist', absolute: false));
        }

        // Admins and others default to dashboard
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
