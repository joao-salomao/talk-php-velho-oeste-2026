<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Models\Ticket;
use Prism\Prism\Tool;

/**
 * Slide 17 — tool com efeito colateral.
 *
 * Toda mutation precisa de allowlist + idempotência + autorização do
 * lado PHP. O LLM nunca é fonte da verdade.
 */
class UpdateTicketStatus extends Tool
{
    public function __construct()
    {
        $this->as('update_ticket_status')
            ->for(<<<DESC
                Update a ticket's status. Allowed values: open, in_progress,
                waiting_customer, resolved. ALWAYS confirm with the user
                before calling this tool — it modifies state.
                DESC)
            ->withNumberParameter('ticket_id', 'Ticket ID')
            ->withStringParameter('status', 'New status')
            ->using($this);
    }

    public function __invoke(int $ticket_id, string $status): string
    {
        // Allowlist — o LLM pode inventar status novos ("almost_done").
        // Validamos do lado PHP, nunca confiando no modelo.
        if (! in_array($status, Ticket::STATUSES, true)) {
            return json_encode([
                'error' => 'Invalid status',
                'allowed' => Ticket::STATUSES,
            ]);
        }

        $ticket = Ticket::find($ticket_id);
        if (! $ticket) {
            return json_encode(['error' => "Ticket #{$ticket_id} not found"]);
        }

        // Idempotência — o agente pode chamar duas vezes em loops ou
        // retries; segunda chamada é no-op explícito.
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
