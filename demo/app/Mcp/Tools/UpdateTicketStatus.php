<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * MCP Tool com efeito colateral. Mesma capability que a tool do agente
 * (App\Agents\Tools\UpdateTicketStatus) — também usada pelo board
 * tickets-app via app.callServerTool('update_ticket_status', ...).
 * Valida tudo do lado PHP: allowlist + idempotência.
 */
class UpdateTicketStatus extends Tool
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
     * @return array<string, mixed>
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

    public function handle(Request $request): Response
    {
        $ticketId = (int) $request->get('ticket_id');
        $status = (string) $request->get('status');

        // Allowlist — o LLM pode inventar status. Validamos do lado PHP.
        if (! in_array($status, Ticket::STATUSES, true)) {
            return Response::json([
                'error' => 'Invalid status',
                'allowed' => Ticket::STATUSES,
            ]);
        }

        $ticket = Ticket::find($ticketId);
        if (! $ticket) {
            return Response::json(['error' => "Ticket #{$ticketId} not found"]);
        }

        // Idempotência — segunda chamada com o mesmo status é no-op.
        if ($ticket->status === $status) {
            return Response::json([
                'ok' => true,
                'unchanged' => true,
                'ticket_id' => $ticket->id,
                'status' => $status,
            ]);
        }

        $previous = $ticket->status;
        $ticket->update(['status' => $status]);

        return Response::json([
            'ok' => true,
            'ticket_id' => $ticket->id,
            'previous_status' => $previous,
            'new_status' => $ticket->status,
        ]);
    }
}
