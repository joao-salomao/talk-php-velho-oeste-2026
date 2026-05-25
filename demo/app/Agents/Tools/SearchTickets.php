<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Tool do Laravel AI SDK: busca tickets. Aceita customer_id em vez de
 * nome — força o modelo a resolver via list_customers antes (encadeamento
 * natural de tools).
 */
class SearchTickets implements Tool
{
    public function name(): string
    {
        return 'search_tickets';
    }

    public function description(): Stringable|string
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

    public function handle(Request $request): Stringable|string
    {
        $args = $request->all();
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
            ->get(['id', 'customer_id', 'subject', 'status', 'priority'])
            ->toJson();
    }
}
