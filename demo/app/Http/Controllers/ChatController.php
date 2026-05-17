<?php

namespace App\Http\Controllers;

use App\Agents\SupportAgent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Streaming SSE. O Prism mesmo cuida do framing — `asEventStreamResponse()`
     * itera o Generator de StreamEvents, emite frames `event: <type>\ndata: <json>`
     * e cuida de buffering. Nosso trabalho aqui vira: validar o input,
     * mapear o histórico pra value objects e devolver.
     */
    public function stream(Request $request): StreamedResponse
    {
        $raw = $request->validate([
            'messages' => ['required', 'array'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string'],
        ])['messages'];

        // Prism aceita objetos Message, não arrays — converte o histórico
        // serializado pelo front-end nos value objects esperados.
        $messages = array_map(
            fn (array $m) => $m['role'] === 'user'
                ? new UserMessage($m['content'])
                : new AssistantMessage($m['content']),
            $raw,
        );

        return SupportAgent::new()
            ->withMessages($messages)
            ->asEventStreamResponse();
    }
}
