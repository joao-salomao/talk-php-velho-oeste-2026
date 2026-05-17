<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Models\Ticket;
use Prism\Prism\Tool;

/**
 * Slide 16 — tool de busca de tickets.
 *
 * Padrão "tool como classe": estende Prism\Tool, configura nome/descrição/
 * parâmetros no construtor e implementa a lógica em __invoke. O ->using($this)
 * faz o Prism chamar o próprio objeto como callable.
 */
class SearchTickets extends Tool
{
    public function __construct()
    {
        $this->as('search_tickets')
            ->for(<<<DESC
                Search support tickets. Filter by status (open, in_progress,
                waiting_customer, resolved), keyword (matches subject/description),
                or customer_id. To filter by customer, first call list_customers
                to resolve the customer's id, then pass it here. Returns up to
                10 results.
                DESC)
            ->withEnumParameter('status', 'Status filter', Ticket::STATUSES, required: false)
            ->withStringParameter('keyword', 'Free-text keyword', required: false)
            ->withNumberParameter('customer_id', 'Customer ID (resolved via list_customers)', required: false)
            ->using($this);
    }

    public function __invoke(
        ?string $status = null,
        ?string $keyword = null,
        ?int $customer_id = null,
    ): string {
        return Ticket::query()
            ->with('customer:id,name,email')
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($keyword, fn ($q, $k) => $q->where(function ($w) use ($k) {
                $w->where('subject', 'like', "%{$k}%")
                    ->orWhere('description', 'like', "%{$k}%");
            }))
            ->when($customer_id, fn ($q, $id) => $q->where('customer_id', $id))
            ->get(['id', 'customer_id', 'subject', 'status', 'priority'])
            ->toJson();
    }
}
