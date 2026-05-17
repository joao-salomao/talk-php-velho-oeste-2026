<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Agents\Tools\ListCustomers;
use App\Agents\Tools\SearchTickets;
use App\Agents\Tools\UpdateTicketStatus;
use App\Mcp\Adapters\PrismToolAdapter;
use App\Mcp\Resources\TicketsApp;
use App\Mcp\Tools\ShowTickets;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Contracts\Transport;

/**
 * Mesmas 3 tools do agente do chat — agora servidas via MCP pra
 * qualquer cliente (Claude Desktop, Cursor, ChatGPT, agente de outro time).
 *
 * O Server::$tools aceita tanto class-strings quanto instâncias. Usamos
 * instâncias pra passar a Prism\Tool já configurada pelo PrismToolAdapter.
 */
#[Name('Support Agent Server')]
#[Version('0.1.0')]
#[Instructions(
    'Tools to triage and update internal support tickets. '.
    'Use list_customers to resolve a customer name to an id, '.
    'then pass that id to search_tickets to filter. '.
    'update_ticket_status mutates state — always confirm with the user first. '.
    'show_tickets renders the ticket list as an interactive board (MCP App) — '.
    'prefer it over search_tickets when the user wants to visualize results.'
)]
class SupportServer extends Server
{
    /** @var array<int, \Laravel\Mcp\Server\Tool|class-string<\Laravel\Mcp\Server\Tool>> */
    protected array $tools = [];

    /** @var array<int, \Laravel\Mcp\Server\Resource|class-string<\Laravel\Mcp\Server\Resource>> */
    protected array $resources = [
        TicketsApp::class,
    ];

    protected array $prompts = [];

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);

        // Tools "tradicionais" via adapter Prism — mesma classe que o
        // SupportAgent já usa no chat UI.
        // Tool de UI (#[RendersApp]) — quando o host suporta MCP Apps,
        // o board de tickets é renderizado em iframe isolado.
        $this->tools = [
            new PrismToolAdapter(new ListCustomers),
            new PrismToolAdapter(new SearchTickets),
            new PrismToolAdapter(new UpdateTicketStatus),
            ShowTickets::class,
        ];
    }
}
