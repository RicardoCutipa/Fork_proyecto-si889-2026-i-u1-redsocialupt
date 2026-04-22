<?php

namespace App\Http\Controllers;

use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller as BaseController;

class MessageController extends BaseController
{
    private MessageService $messageService;

    public function __construct()
    {
        $this->messageService = new MessageService();
    }

    /**
     * POST /api/chat/messages
     * Enviar mensaje a un compañero (RF-08).
     */
    public function send(Request $request): JsonResponse
    {
        $this->validate($request, [
            'receiver_id' => 'required|integer',
            'content'     => 'nullable|string',
            'image_url'   => 'nullable|string|url',
        ]);

        try {
            $message = $this->messageService->send(
                $request->auth->sub,
                $request->input('receiver_id'),
                $request->input('content'),
                $request->input('image_url')
            );
            return response()->json($message, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/chat/messages/{userId}
     * Obtener conversación con un usuario (RF-08).
     * Frontend usa polling cada 3s sobre este endpoint.
     */
    public function conversation(Request $request, int $userId): JsonResponse
    {
        $limit    = (int) $request->query('limit', 50);
        $messages = $this->messageService->getConversation($request->auth->sub, $userId, $limit);

        return response()->json($messages, 200);
    }

    /**
     * GET /api/chat/inbox
     * Lista de conversaciones recientes con último mensaje (RF-08).
     */
    public function inbox(Request $request): JsonResponse
    {
        $conversations = $this->messageService->getInbox($request->auth->sub);
        return response()->json($conversations, 200);
    }
}
