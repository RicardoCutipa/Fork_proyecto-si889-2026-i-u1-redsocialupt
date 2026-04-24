<?php

namespace App\Http\Controllers;

use App\Services\CommentLikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class CommentLikeController extends BaseController
{
    private CommentLikeService $commentLikeService;

    public function __construct()
    {
        $this->commentLikeService = new CommentLikeService();
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->commentLikeService->toggle($request->auth->sub, $id);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function count(int $id): JsonResponse
    {
        return response()->json(['count' => $this->commentLikeService->count($id)], 200);
    }
}
