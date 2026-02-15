<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
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
            'master_password' => ['required'],
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        if ($request->master_password !== env('MASTER_REGISTER_PASSWORD')) {
            return back()->withErrors([
            'master_password' => 'La contraseña maestra es incorrecta.',
        ]);
        }       

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // Todo usuario registrado entra como docente por defecto;
            // el administrador luego puede cambiar el rol si corresponde.
            'role' => Role::ADMINISTRADOR->value,
            'email_verified_at' => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Redirección de conveniencia tras el registro
        if ($user->isAdmin()) {
            return to_route('dashboard');
        }

        if ($user->isResponsable() || $user->isDocente()) {
            return to_route('cursos.index');
        }

        return to_route('dashboard');
    }
}

