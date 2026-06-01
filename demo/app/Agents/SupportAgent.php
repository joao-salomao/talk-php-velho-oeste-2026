<?php

declare(strict_types=1);

namespace App\Agents;

use App\Agents\Tools\ListCustomers;
use App\Agents\Tools\SearchTickets;
use App\Agents\Tools\UpdateTicketStatus;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[Model('claude-sonnet-4-6')]
#[MaxSteps(8)]
final class SupportAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function __construct(private array $history = []) {}

    public function instructions(): Stringable|string
    {
        return <<<SYS
            You are a support agent assistant. Help the human triage and
            update support tickets.
            - Use list_customers to resolve a customer name to an id.
            - Use search_tickets with that id (never invent ticket data).
            - ALWAYS confirm before calling update_ticket_status.
            - Be concise.
            SYS;
    }

    /**
     * Histórico da conversa (turnos anteriores). O turno atual entra pelo
     * argumento de ->stream()/->prompt(), não aqui.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return array_map(
            fn (array $m) => new Message($m['role'], $m['content']),
            $this->history,
        );
    }

    /**
     * @return \Laravel\Ai\Contracts\Tool[]
     */
    public function tools(): iterable
    {
        return [
            new ListCustomers,
            new SearchTickets,
            new UpdateTicketStatus,
        ];
    }
}
