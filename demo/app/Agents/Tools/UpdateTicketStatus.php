<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Actions\UpdateTicketStatusAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Binding Laravel AI da capability update_ticket_status. Lógica (allowlist,
 * idempotência, mutation) em UpdateTicketStatusAction — mesma Action
 * servida via MCP (e usada pelo board tickets-app).
 */
class UpdateTicketStatus implements Tool
{
    public function __construct(
        private readonly UpdateTicketStatusAction $action = new UpdateTicketStatusAction,
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
