<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * MCP Tool: busca tickets. Mesma capability que a tool do agente
 * (App\Agents\Tools\SearchTickets), aqui no contrato do Laravel MCP.
 */
class SearchTickets extends Tool
{
    public function name(): string
    {
        return 'search_tickets';
    }

    public function description(): string
    {
        return <<<DESC
            Search support tickets. Filter by status (open, in_progress,
            waiting_customer, resolved), keyword (matches subject/description),
            or customer_id. To filter by customer, first call list_customers
            to resolve the customer's id, then pass it here. Returns up to
            10 results.
            DESC;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description('Status filter.')
                ->enum(Ticket::STATUSES),
            'keyword' => $schema->string()
                ->description('Free-text keyword (subject/description).'),
            'customer_id' => $schema->integer()
                ->description('Customer ID (resolved via list_customers).'),
        ];
    }

    public function handle(Request $request): Response
    {
        $status = $request->get('status');
        $keyword = $request->get('keyword');
        $customerId = $request->get('customer_id');

        $tickets = Ticket::query()
            ->with('customer:id,name,email')
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($keyword, fn ($q, $k) => $q->where(function ($w) use ($k) {
                $w->where('subject', 'like', "%{$k}%")
                    ->orWhere('description', 'like', "%{$k}%");
            }))
            ->when($customerId, fn ($q, $id) => $q->where('customer_id', $id))
            ->limit(10)
            ->get(['id', 'customer_id', 'subject', 'status', 'priority']);

        return Response::json($tickets);
    }
}
