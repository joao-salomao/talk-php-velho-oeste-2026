<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Fonte única da capability "list_customers": nome, description (prompt
 * que ensina o modelo), schema dos parâmetros e a query em si.
 *
 * Consumida por DOIS surfaces — sem duplicar lógica:
 *  - App\Agents\Tools\ListCustomers   (Laravel AI — chat do agente)
 *  - App\Mcp\Tools\ListCustomers      (Laravel MCP — servidor)
 */
class ListCustomersAction
{
    public function name(): string
    {
        return 'list_customers';
    }

    public function description(): string
    {
        return 'List customers with their ticket count. Optionally filter by a keyword that matches name or email (case-insensitive). Returns up to 20.';
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'keyword' => $schema->string()
                ->description('Optional keyword (matches name or email partial).'),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(array $args): string
    {
        $keyword = $args['keyword'] ?? null;

        return Customer::query()
            ->withCount('tickets')
            ->when($keyword, fn ($q, $k) => $q->where(function ($w) use ($k) {
                $w->where('name', 'like', "%{$k}%")
                    ->orWhere('email', 'like', "%{$k}%");
            }))
            ->orderBy('name')
            // limit(20) cap no payload — modelo refina o keyword se precisar.
            ->limit(20)
            ->get(['id', 'name', 'email'])
            ->toJson();
    }
}
