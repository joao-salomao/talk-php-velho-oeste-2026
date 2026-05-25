<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Tool do Laravel AI SDK: read-only, lista customers com contagem de
 * tickets. Implementa Tool (name/description/schema/handle).
 * A `description` é o prompt que ensina o modelo quando usar.
 */
class ListCustomers implements Tool
{
    public function name(): string
    {
        return 'list_customers';
    }

    public function description(): Stringable|string
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

    public function handle(Request $request): Stringable|string
    {
        $keyword = $request->all()['keyword'] ?? null;

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
