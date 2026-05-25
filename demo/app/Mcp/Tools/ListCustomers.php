<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * MCP Tool: lista customers com contagem de tickets. Mesma capability que
 * a tool homônima do agente (App\Agents\Tools\ListCustomers) — aqui no
 * contrato do Laravel MCP (handle(Request): Response).
 */
class ListCustomers extends Tool
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
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'keyword' => $schema->string()
                ->description('Optional keyword (matches name or email partial).'),
        ];
    }

    public function handle(Request $request): Response
    {
        $keyword = $request->get('keyword');

        $customers = Customer::query()
            ->withCount('tickets')
            ->when($keyword, fn ($q, $k) => $q->where(function ($w) use ($k) {
                $w->where('name', 'like', "%{$k}%")
                    ->orWhere('email', 'like', "%{$k}%");
            }))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Response::json($customers);
    }
}
