<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Lista as notificações do usuário logado com paginação e cache.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 50);
        $unreadOnly = $request->boolean('unread_only', false);

        // Cache key baseado no usuário e filtros
        $cacheKey = "notifications:user:{$user->id}:unread:" . ($unreadOnly ? '1' : '0');
        
        // Cache por 30 segundos para melhor performance
        $notifications = Cache::remember($cacheKey, 30, function () use ($user, $perPage, $unreadOnly) {
            $query = $user->notifications()->latest();
            
            if ($unreadOnly) {
                $query->whereNull('read_at');
            }
            
            return $query->take($perPage)->get();
        });

        // Conta não lidas sem cache para precisão
        $unreadCount = Cache::remember("notifications:unread_count:{$user->id}", 30, function () use ($user) {
            return $user->unreadNotifications()->count();
        });

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'unread_count' => $unreadCount,
                'total' => $notifications->count(),
            ],
        ]);
    }

    /**
     * Marca uma notificação específica como lida.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notificação não encontrada'
            ], 404);
        }

        if (!$notification->read_at) {
            $notification->markAsRead();
            
            // Limpa cache relacionado
            $this->clearNotificationCache($user->id);
        }

        return response()->json([
            'message' => 'Notificação marcada como lida',
            'data' => new NotificationResource($notification)
        ]);
    }

    /**
     * Marca TODAS as notificações como lidas.
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        
        $unreadCount = $user->unreadNotifications()->count();
        
        if ($unreadCount > 0) {
            // Usa chunk para evitar problemas de memória com muitas notificações
            $user->unreadNotifications()->chunk(100, function ($notifications) {
                foreach ($notifications as $notification) {
                    $notification->markAsRead();
                }
            });
            
            // Limpa cache relacionado
            $this->clearNotificationCache($user->id);
        }

        return response()->json([
            'message' => 'Todas as notificações foram marcadas como lidas',
            'marked_count' => $unreadCount
        ]);
    }

    /**
     * Retorna apenas a contagem de notificações não lidas (endpoint leve para polling).
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        
        $count = Cache::remember("notifications:unread_count:{$user->id}", 30, function () use ($user) {
            return $user->unreadNotifications()->count();
        });

        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Limpa o cache de notificações do usuário.
     */
    private function clearNotificationCache(int $userId): void
    {
        Cache::forget("notifications:user:{$userId}:unread:0");
        Cache::forget("notifications:user:{$userId}:unread:1");
        Cache::forget("notifications:unread_count:{$userId}");
    }
}
