<?php

namespace App\Http\Controllers\Api;

use App\Assistant\Assistant;
use App\Http\Controllers\Controller;
use App\Http\Resources\UmkmResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chat assistant: `POST /api/assistant/chat`.
 *
 * Request:  { message, context?, history? }
 * Response: { reply, context, results[], total, detailId, source }
 *
 * `results` are real UmkmResource rows from the database search; `reply` is
 * either the assistant's own template text or Claude's wording of those same
 * rows (`source`: "rules" / "ai"). The client stores `context` and sends it
 * back with the next message - that is the whole conversation state.
 */
class AssistantController extends Controller
{
    public function __construct(private readonly Assistant $assistant) {}

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'context' => ['nullable', 'array'],
            'history' => ['nullable', 'array', 'max:12'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.text' => ['required_with:history', 'string', 'max:2000'],
        ], [
            'message.required' => 'Tulis pertanyaanmu terlebih dahulu.',
            'message.max' => 'Pertanyaan maksimal 500 karakter.',
        ]);

        $history = array_map(
            fn ($turn) => ['role' => $turn['role'], 'text' => $turn['text']],
            array_slice($data['history'] ?? [], -12),
        );

        $result = $this->assistant->answer(trim($data['message']), $data['context'] ?? [], $history);

        return response()->json([
            'reply' => $result['reply'],
            'context' => $result['context'],
            'results' => UmkmResource::collection($result['results']),
            'total' => $result['total'],
            'detailId' => $result['detail']?->id,
            'source' => $result['source'],
        ]);
    }
}
