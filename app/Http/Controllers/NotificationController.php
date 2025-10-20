<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function readAll(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
        return back();
    }

    public function read(Request $request, DatabaseNotification $notification)
    {
        if ($request->user()?->id !== $notification->notifiable_id) {
            abort(403);
        }
        $notification->markAsRead();
        return back();
    }
}
