<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'csrf_token' => csrf_token(),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'notifications' => $request->user()?->unreadNotifications()->latest()->limit(10)->get()->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->data['type'] ?? null,
                    'message' => $n->data['message'] ?? null,
                    'link' => $n->data['link'] ?? null,
                    'created_at' => $n->created_at?->toIso8601String(),
                ];
            }) ?? [],
            'notifications_all' => $request->user()?->notifications()->latest()->limit(20)->get()->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->data['type'] ?? null,
                    'message' => $n->data['message'] ?? null,
                    'link' => $n->data['link'] ?? null,
                    'created_at' => $n->created_at?->toIso8601String(),
                    'read_at' => $n->read_at?->toIso8601String(),
                ];
            }) ?? [],
            'notifications_count' => $request->user()?->unreadNotifications()->count() ?? 0,
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
