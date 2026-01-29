<?php

namespace App\Http\Controllers;

use App\Models\Update;
use App\Models\UpdateRead;
use App\Jobs\NotifyUpdateAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class UpdateController extends Controller
{
    protected function cacheKey(Request $request): string
    {
        $userId = $request->user()->id;
        $estado = $request->get('estado', 'activas');
        $aud = $request->get('audiencia', '');
        $from = $request->get('from', '');
        $to = $request->get('to', '');

        return "updates.list.{$userId}.{$estado}.{$aud}.{$from}.{$to}";
    }

    protected function clearCache(): void
    {
        Cache::flush();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $estado = $request->get('estado', 'activas'); // activas, proximas, vencidas, todas
        $audiencia = $request->get('audiencia');
        $from = $request->get('from');
        $to = $request->get('to');

        $key = $this->cacheKey($request);

        $updates = Cache::remember($key, now()->addMinutes(10), function () use ($user, $estado, $audiencia, $from, $to) {
            $now = now();

            $query = Update::with(['reads', 'creator'])->visibleFor($user);

            switch ($estado) {
                case 'proximas':
                    $query->where('starts_at', '>', $now);
                    break;
                case 'vencidas':
                    $query->where(function ($q) use ($now) {
                        $q->whereNotNull('ends_at')
                            ->where('ends_at', '<', $now);
                    });
                    break;
                case 'todas':
                    // sin filtro
                    break;
                default: // activas
                    $query->where('starts_at', '<=', $now)
                        ->where(function ($q) use ($now) {
                            $q->whereNull('ends_at')
                                ->orWhere('ends_at', '>=', $now);
                        });
                    break;
            }

            if ($audiencia) {
                $query->where('audience', $audiencia);
            }

            if ($from) {
                $query->where('starts_at', '>=', $from);
            }
            if ($to) {
                $query->where('starts_at', '<=', $to);
            }

            $query->orderByDesc('pinned')->orderByDesc('starts_at');

            return $query->get()->map(function (Update $u) use ($user) {
                $totalReads = $u->reads->count();
                $leido = $u->reads->contains(fn ($r) => $r->user_id === $user->id);

                return [
                    'id' => $u->id,
                    'titulo' => $u->titulo,
                    'cuerpo_md' => $u->cuerpo_md,
                    'audience' => $u->audience,
                    'pinned' => $u->pinned,
                    'starts_at' => $u->starts_at?->toIso8601String(),
                    'ends_at' => $u->ends_at?->toIso8601String(),
                    'creador' => $u->creator?->name,
                    'reads_count' => $totalReads,
                    'leido' => $leido,
                    'is_active' => $u->isActiveNow(),
                ];
            });
        });

        return Inertia::render('Actualizaciones/Index', [
            'updates' => $updates,
            'filters' => [
                'estado' => $estado,
                'audiencia' => $audiencia,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Update::class);

        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'cuerpo_md' => 'required|string|min:10',
            'audience' => 'required|in:TODOS,DOCENTES,RESPONSABLES,ADMIN',
            'pinned' => 'boolean',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $update = Update::create([
            ...$data,
            'creado_por' => $request->user()->id,
        ]);

        if ($update->isActiveNow() || $update->pinned) {
            NotifyUpdateAudience::dispatchAfterResponse($update->id);
        }

        $this->clearCache();

        return back()->with('success', 'Actualización creada');
    }

    public function update(Request $request, Update $update)
    {
        $this->authorize('update', $update);

        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'cuerpo_md' => 'required|string|min:10',
            'audience' => 'required|in:TODOS,DOCENTES,RESPONSABLES,ADMIN',
            'pinned' => 'boolean',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $wasActive = $update->isActiveNow();

        $update->update($data);

        if ((! $wasActive && $update->isActiveNow()) || $update->pinned) {
            NotifyUpdateAudience::dispatchAfterResponse($update->id);
        }

        $this->clearCache();

        return back()->with('success', 'Actualización actualizada');
    }

    public function destroy(Update $update)
    {
        $this->authorize('delete', $update);

        $update->delete();
        $this->clearCache();

        return back()->with('success', 'Actualización eliminada');
    }

    public function pin(Update $update)
    {
        $this->authorize('pin', $update);
        $update->update(['pinned' => true]);
        NotifyUpdateAudience::dispatchAfterResponse($update->id);
        $this->clearCache();

        return back()->with('success', 'Actualización fijada');
    }

    public function unpin(Update $update)
    {
        $this->authorize('unpin', $update);
        $update->update(['pinned' => false]);
        $this->clearCache();

        return back()->with('success', 'Actualización desfijada');
    }

    public function markRead(Request $request, Update $update)
    {
        $user = $request->user();
        $this->authorize('view', $update);

        UpdateRead::firstOrCreate(
            [
                'update_id' => $update->id,
                'user_id' => $user->id,
            ],
            ['read_at' => now()],
        );

        return back()->with('success', 'Actualización marcada como leída');
    }

    public function counter(Request $request)
    {
        $user = $request->user();

        $now = now();

        $count = Update::visibleFor($user)
            ->where('starts_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();

        return response()->json(['unread' => $count]);
    }

}
