<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\SearchTicketsAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Binding MCP da capability search_tickets. Mesma SearchTicketsAction do
 * agente Laravel AI.
 */
class SearchTickets extends Tool
{
    public function __construct(
        private readonly SearchTicketsAction $action = new SearchTicketsAction,
    ) {}

    public function name(): string
    {
        return $this->action->name();
    }

    public function title(): string
    {
        return Str::headline($this->action->name());
    }

    public function description(): string
    {
        return $this->action->description();
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->action->schema($schema);
    }

    public function handle(Request $request): Response
    {
        return Response::text(($this->action)($request->all()));
    }
}
