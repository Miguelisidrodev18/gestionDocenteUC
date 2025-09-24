<?php

namespace App\Http\Controllers;

use App\Models\CourseDocument;
use App\Models\Curso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChecklistController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $cursos = Curso::with([
            'docente',
            'responsable',
            'documents' => fn ($query) => $query->latest(),
            'documents.docente',
        ])->when($user && ! $user->isAdmin(), function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        return Inertia::render('Cursos/Checklist', [
            'cursos' => $cursos,
        ]);
    }

    public function update(Request $request, CourseDocument $document): RedirectResponse
    {
        $request->validate([
            'preliminar' => 'nullable|boolean',
            'final' => 'nullable|boolean',
        ]);

        $user = $request->user();

        if (! $user || (! $user->isAdmin() && $user->role !== 'responsable')) {
            abort(403, 'No tienes acceso para actualizar el checklist.');
        }

        $currentStatus = $document->status;
        $preliminar = $request->has('preliminar')
            ? $request->boolean('preliminar')
            : in_array($currentStatus, ['conforme_preliminar', 'validado'], true);
        $final = $request->has('final')
            ? $request->boolean('final')
            : $currentStatus === 'validado';

        if ($user->role === 'responsable' && ! $user->isAdmin()) {
            $final = $currentStatus === 'validado';
        }

        $newStatus = 'pendiente';
        if ($final) {
            $newStatus = 'validado';
        } elseif ($preliminar) {
            $newStatus = 'conforme_preliminar';
        }

        $document->update(['status' => $newStatus]);

        return back()->with('success', 'Checklist actualizado correctamente.');
    }
}
