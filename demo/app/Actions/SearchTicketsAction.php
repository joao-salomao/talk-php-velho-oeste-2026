<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Fonte única da capability "search_tickets" — consumida pelo agente
 * (Laravel AI) e pelo servidor MCP, sem duplicar lógica.
 */
class SearchTicketsAction
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
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
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

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(array $args): string
    {
        $status = $args['status'] ?? null;
        $keyword = $args['keyword'] ?? null;
        $customerId = $args['customer_id'] ?? null;

        return Ticket::query()
            ->with('customer:id,name,email')
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($keyword, fn ($q, $k) => $q->where(function ($w) use ($k) {
                $w->where('subject', 'like', "%{$k}%")
                    ->orWhere('description', 'like', "%{$k}%");
            }))
            ->when($customerId, fn ($q, $id) => $q->where('customer_id', $id))
            ->limit(10)
            ->get(['id', 'customer_id', 'subject', 'status', 'priority'])
            ->toJson();
    }
}
