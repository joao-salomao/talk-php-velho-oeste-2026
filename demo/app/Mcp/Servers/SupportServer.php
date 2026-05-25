<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Resources\TicketsApp;
use App\Mcp\Tools\ListCustomers;
use App\Mcp\Tools\SearchTickets;
use App\Mcp\Tools\ShowTickets;
use App\Mcp\Tools\UpdateTicketStatus;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Mesmas capabilities do agente do chat — agora servidas via MCP pra
 * qualquer cliente (Claude Desktop, Cursor, ChatGPT, agente de outro time).
 *
 * Cada MCP Tool delega pra mesma Action (App\Actions\*) que o agente
 * Laravel AI usa — uma definição, duas superfícies, sem adapter.
 *
 * ShowTickets é uma tool de UI (#[RendersApp]): quando o host suporta MCP
 * Apps, o board de tickets é renderizado num iframe isolado.
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
    /** @var array<int, class-string<\Laravel\Mcp\Server\Tool>> */
    protected array $tools = [
        ListCustomers::class,
        SearchTickets::class,
        UpdateTicketStatus::class,
        ShowTickets::class,
    ];

    /** @var array<int, class-string<\Laravel\Mcp\Server\Resource>> */
    protected array $resources = [
        TicketsApp::class,
    ];

    protected array $prompts = [];
}
