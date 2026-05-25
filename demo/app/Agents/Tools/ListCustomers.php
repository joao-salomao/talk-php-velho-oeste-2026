<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Actions\ListCustomersAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Binding Laravel AI da capability list_customers. Toda a lógica (query,
 * description, schema) vive em ListCustomersAction — esta classe só adapta
 * pro contrato do laravel/ai. A mesma Action é servida via MCP.
 */
class ListCustomers implements Tool
{
    public function __construct(
        private readonly ListCustomersAction $action = new ListCustomersAction,
    ) {}

    public function name(): string
    {
        return $this->action->name();
    }

    public function description(): Stringable|string
    {
        return $this->action->description();
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->action->schema($schema);
    }

    public function handle(Request $request): Stringable|string
    {
        return ($this->action)($request->all());
    }
}
