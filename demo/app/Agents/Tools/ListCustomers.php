<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Models\Customer;
use Prism\Prism\Tool;

/**
 * Lista customers com contagem de tickets. Aceita um keyword opcional
 * pra filtrar por nome ou email — quando ausente, devolve os 20
 * primeiros.
 */
class ListCustomers extends Tool
{
    public function __construct()
    {
        $this->as('list_customers')
            ->for(<<<DESC
                List customers with their ticket count. Optionally filter
                by a keyword that matches either the customer name or
                email (case-insensitive). Returns up to 20 results.
                DESC)
            ->withStringParameter(
                'keyword',
                'Optional keyword (matches name or email partial).',
                required: false,
            )
            ->using($this);
    }

    public function __invoke(?string $keyword = null): string
    {
        return Customer::query()
            ->withCount('tickets')
            ->when($keyword, fn ($q, $k) => $q->where(function ($w) use ($k) {
                $w->where('name', 'like', "%{$k}%")
                    ->orWhere('email', 'like', "%{$k}%");
            }))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->toJson();
    }
}
