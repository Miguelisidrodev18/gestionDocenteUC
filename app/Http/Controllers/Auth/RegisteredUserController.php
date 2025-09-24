<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,docente,responsable', // Validación para el rol
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role, // Asigna el rol
            'email_verified_at' => now(),
        ]);

        if (in_array($request->role, ['docente', 'responsable'], true)) {
            [$nombre, $apellido] = array_pad(preg_split('/\s+/', trim($request->name), 2), 2, '');
            $nombre = trim((string) $nombre);
            $apellido = trim((string) $apellido);

            Docente::create([
                'user_id' => $user->id,
                'nombre' => $nombre !== '' ? $nombre : $request->name,
                'apellido' => $apellido !== '' ? $apellido : 'Pendiente',
                'dni' => sprintf('TMP-%06d', $user->id),
                'email' => $user->email,
                'telefono' => null,
                'especialidad' => 'Pendiente',
                'cv_personal' => null,
                'cv_sunedu' => null,
                'linkedin' => null,
                'estado' => 'activo',
                'cip' => null,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return match ($user->role) {
            'admin' => to_route('dashboard'),
            'responsable', 'docente' => to_route('cursos.index'),
            default => to_route('dashboard'),
        };
    }
}
