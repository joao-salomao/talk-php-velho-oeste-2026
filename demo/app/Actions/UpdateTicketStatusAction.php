<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Fonte única da capability "update_ticket_status" — consumida pelo agente
 * (Laravel AI) e pelo servidor MCP (inclusive o board tickets-app).
 *
 * Tool com efeito colateral: validar tudo, o LLM não é fonte da verdade.
 */
class UpdateTicketStatusAction
{
    public function name(): string
    {
        return 'update_ticket_status';
    }

    public function description(): string
    {
        return <<<DESC
            Update a ticket's status. Allowed values: open, in_progress,
            waiting_customer, resolved. ALWAYS confirm with the user
            before calling this tool — it modifies state.
            DESC;
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()
                ->description('Ticket ID.')
                ->required(),
            'status' => $schema->string()
                ->description('New status.')
                ->enum(Ticket::STATUSES)
                ->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(array $args): string
    {
        $ticketId = (int) ($args['ticket_id'] ?? 0);
        $status = (string) ($args['status'] ?? '');

        // Allowlist — o LLM pode inventar status novos ("almost_done").
        // Validamos do lado PHP, nunca confiando no modelo.
        if (! in_array($status, Ticket::STATUSES, true)) {
            return json_encode([
                'error' => 'Invalid status',
                'allowed' => Ticket::STATUSES,
            ]);
        }

        $ticket = Ticket::find($ticketId);
        if (! $ticket) {
            return json_encode(['error' => "Ticket #{$ticketId} not found"]);
        }

        // Idempotência — o agente pode chamar duas vezes em loops/retries;
        // segunda chamada é no-op explícito.
        if ($ticket->status === $status) {
            return json_encode([
                'ok' => true,
                'unchanged' => true,
                'ticket_id' => $ticket->id,
                'status' => $status,
            ]);
        }

        $previous = $ticket->status;
        $ticket->update(['status' => $status]);

        return json_encode([
            'ok' => true,
            'ticket_id' => $ticket->id,
            'previous_status' => $previous,
            'new_status' => $ticket->status,
        ]);
    }
}
