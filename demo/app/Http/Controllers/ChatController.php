<?php

namespace App\Http\Controllers;

use App\Agents\SupportAgent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Responses\StreamableAgentResponse;

class ChatController extends Controller
{
    /**
     * Renderiza a página de chat (Inertia/Vue).
     */
    public function index(): Response
    {
        return Inertia::render('Chat');
    }

    /**
     * Streaming SSE com o Laravel AI SDK.
     *
     * O histórico chega do front-end com o turno atual como última msg:
     * tudo antes vira o messages() do agente; a última (user) vira o
     * prompt de ->stream().
     *
     * O StreamableAgentResponse é Responsable — o Laravel chama toResponse()
     * sozinho, que faz o streaming SSE (cada StreamEvent vira `data: <json>`,
     * com o tipo dentro do JSON, e fecha com `data: [DONE]`).
     */
    public function stream(Request $request): StreamableAgentResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $messages = $validated['messages'];

        // última mensagem enviada pelo usuário
        $current = array_pop($messages);

        return SupportAgent::make($messages)->stream($current['content']);
    }
}
