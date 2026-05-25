<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Actions\SearchTicketsAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Binding Laravel AI da capability search_tickets. Lógica em
 * SearchTicketsAction — mesma Action servida via MCP.
 */
class SearchTickets implements Tool
{
    public function __construct(
        private readonly SearchTicketsAction $action = new SearchTicketsAction,
    ) {}

    public function name(): string
    {
        return $this->action->name();
    }

    public function description(): Stringable|string
    {
        return $this->action->description();
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->action->schema($schema);
    }

    public function handle(Request $request): Stringable|string
    {
        return ($this->action)($request->all());
    }
}
