<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Tool do Laravel AI SDK com efeito colateral. Validar tudo: o LLM não é
 * fonte da verdade — allowlist de status + idempotência do lado PHP.
 */
class UpdateTicketStatus implements Tool
{
    public function name(): string
    {
        return 'update_ticket_status';
    }

    public function description(): Stringable|string
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

    public function handle(Request $request): Stringable|string
    {
        $args = $request->all();
        $ticketId = (int) ($args['ticket_id'] ?? 0);
        $status = (string) ($args['status'] ?? '');

        // Allowlist — o LLM pode inventar status ("almost_done").
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

        // Idempotência — o agente pode chamar duas vezes; 2ª é no-op.
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
