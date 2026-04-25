<?php

namespace App\Services;

use App\Models\Message;

class MessageService
{
    /**
     * Enviar mensaje a un compañero (RF-08).
     */
    public function send(int $senderId, int $receiverId, ?string $content, ?string $imageUrl): Message
    {
        if ($senderId === $receiverId) {
            throw new \Exception('No puedes enviarte un mensaje a ti mismo', 422);
        }

        if (empty($content) && empty($imageUrl)) {
            throw new \Exception('El mensaje debe tener contenido o imagen', 422);
        }

        return Message::create([
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'content'     => $content,
            'image_url'   => $imageUrl,
            'is_read'     => false,
        ]);
    }

    /**
     * Obtener conversación entre dos usuarios (RF-08).
     * Retorna mensajes ordenados cronológicamente.
     */
    public function getConversation(int $userId, int $otherUserId, int $limit = 50): array
    {
        $messages = Message::where(function ($q) use ($userId, $otherUserId) {
            $q->where('sender_id', $userId)->where('receiver_id', $otherUserId);
        })->orWhere(function ($q) use ($userId, $otherUserId) {
            $q->where('sender_id', $otherUserId)->where('receiver_id', $userId);
        })
        ->orderBy('created_at', 'asc')
        ->limit($limit)
        ->get()
        ->toArray();

        // Marcar como leídos los mensajes recibidos
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $messages;
    }

    /**
     * Inbox: lista de conversaciones recientes con último mensaje (RF-08).
     */
    public function getInbox(int $userId): array
    {
        // Obtener IDs de usuarios con los que ha chateado
        $sentTo = Message::where('sender_id', $userId)->pluck('receiver_id');
        $receivedFrom = Message::where('receiver_id', $userId)->pluck('sender_id');

        $contactIds = $sentTo->merge($receivedFrom)->unique()->values();

        $conversations = [];
        foreach ($contactIds as $contactId) {
            $lastMessage = Message::where(function ($q) use ($userId, $contactId) {
                $q->where('sender_id', $userId)->where('receiver_id', $contactId);
            })->orWhere(function ($q) use ($userId, $contactId) {
                $q->where('sender_id', $contactId)->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->first();

            $unreadCount = Message::where('sender_id', $contactId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->count();

            if ($lastMessage) {
                $conversations[] = [
                    'contact_id'   => $contactId,
                    'last_message' => $lastMessage->toArray(),
                    'unread_count' => $unreadCount,
                ];
            }
        }

        // Ordenar por último mensaje más reciente
        usort($conversations, fn($a, $b) =>
            strtotime($b['last_message']['created_at']) - strtotime($a['last_message']['created_at'])
        );

        return $conversations;
    }
}
