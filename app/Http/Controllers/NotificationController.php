<?php

namespace App\Http\Controllers;

use App\Models\HrNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread_count' => 0, 'notifications' => []]);
        }

        $userId = $user->id;
        $currentRole = session('current_role');
        $activeEmp = \Modules\Employee\Models\Employee::where('user_id', $userId)->first();

        $targetUserIds = [$userId];

        // If Developer Mode / Role Switcher is used, also include notifications for the switched role's employee user
        if ($currentRole && $activeEmp && $activeEmp->system_role !== $currentRole) {
            $roleEmps = \Modules\Employee\Models\Employee::where('system_role', $currentRole)->get();
            foreach ($roleEmps as $re) {
                if ($re->user_id) {
                    $targetUserIds[] = $re->user_id;
                }
            }
        }

        $targetUserIds = array_unique($targetUserIds);

        $notifications = HrNotification::where(function ($q) use ($targetUserIds) {
            $q->whereIn('user_id', $targetUserIds)->orWhereNull('user_id');
        })->latest()->take(30)->get();

        $unreadCount = HrNotification::where(function ($q) use ($targetUserIds) {
            $q->whereIn('user_id', $targetUserIds)->orWhereNull('user_id');
        })->where('is_read', false)->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead($id): JsonResponse
    {
        $notification = HrNotification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => true]);
        }

        $userId = $user->id;
        $currentRole = session('current_role');
        $activeEmp = \Modules\Employee\Models\Employee::where('user_id', $userId)->first();

        $targetUserIds = [$userId];
        if ($currentRole && $activeEmp && $activeEmp->system_role !== $currentRole) {
            $roleEmps = \Modules\Employee\Models\Employee::where('system_role', $currentRole)->get();
            foreach ($roleEmps as $re) {
                if ($re->user_id) {
                    $targetUserIds[] = $re->user_id;
                }
            }
        }

        $targetUserIds = array_unique($targetUserIds);

        HrNotification::where(function ($q) use ($targetUserIds) {
            $q->whereIn('user_id', $targetUserIds)->orWhereNull('user_id');
        })->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
