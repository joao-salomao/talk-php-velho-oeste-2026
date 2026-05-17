<?php

declare(strict_types=1);

namespace App\Agents;

use App\Agents\Tools\ListCustomers;
use App\Agents\Tools\SearchTickets;
use App\Agents\Tools\UpdateTicketStatus;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;

/**
 *
 * Compõe o pipeline com Prism::text() + duas tools como classes.
 * Retorna o PendingRequest — o caller decide como consumir
 * (asStream() pro REPL/chat UI, asText() pra resposta one-shot).
 *
 * Sem classe Agent, sem framework agent-first — é só Prism encadeado.
 */
final class SupportAgent
{
    private const SYSTEM_PROMPT = <<<EOL
        You are a support agent assistant. You help a HUMAN agent triage
        and update support tickets.

        Rules:
        - Use list_customers when the human asks who the customers are
          or wants ticket counts per customer.
        - Use search_tickets to find tickets — never invent ticket data.
        - ALWAYS confirm with the user before calling update_ticket_status.
        - Be concise. The human is busy and reads fast.
    EOL;

    public static function new(): PendingRequest
    {
        return Prism::text()
            ->using(Provider::Anthropic, 'claude-sonnet-4-6')
            ->withMaxSteps(8)
            ->withSystemPrompt(self::SYSTEM_PROMPT)
            ->withTools([
                new ListCustomers,
                new SearchTickets,
                new UpdateTicketStatus,
            ]);
    }
}
