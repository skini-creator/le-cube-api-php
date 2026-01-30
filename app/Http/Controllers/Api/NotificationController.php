<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'notifications' => $notifications]);
    }

    public function unread(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->unread()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'count' => $notifications->count()
        ]);
    }

    public function markAsRead($id, Request $request)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true, 'message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->notifications()->unread()->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
    }

    public function destroy($id, Request $request)
    {
        $request->user()->notifications()->findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Notification deleted']);
    }
}