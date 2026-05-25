<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use App\Models\Customer;
use App\Models\Ticket;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;

/**
 * MCP Prompt — o terceiro primitive do protocolo (tools + resources +
 * prompts). Um prompt é um template reutilizável que o host injeta na
 * conversa (ex.: slash command no Claude Desktop). Aqui ele computa um
 * snapshot ao vivo dos tickets (prompts podem ser dinâmicos!) e instrui
 * o agente a montar um relatório usando as tools do servidor.
 */
#[Description('Generate a general support operations report over the current tickets (volume by status/priority, top customers, urgent items, next actions).')]
class TicketReport extends Prompt
{
    public function name(): string
    {
        return 'ticket_report';
    }

    public function handle(Request $request): Response
    {
        return Response::text(<<<PROMPT
            You are compiling a support operations report for a human team lead.

            Produce a concise **markdown** report with these sections:
            1. **Overview** — volume by status and priority (use the snapshot above).
            2. **Top customers** — who has the most open tickets. Call `list_customers`
               (ticket counts) and `search_tickets` to back this up.
            3. **Needs attention now** — urgent/high-priority open tickets. Use
               `search_tickets` with status=open and inspect priorities. Cite ticket IDs.
            4. **Recommended next actions** — 3-5 concrete bullets.

            Keep it skimmable. Do not invent ticket data — always use the tools.
            PROMPT);
    }
}
