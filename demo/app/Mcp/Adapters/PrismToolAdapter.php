<?php

declare(strict_types=1);

namespace App\Mcp\Adapters;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool as McpTool;
use Prism\Prism\Tool as PrismTool;

/**
 * Adapter que expõe um Prism\Tool como Laravel\Mcp\Server\Tool — "1 código,
 * N superfícies": a mesma classe que o agente Prism usa in-process também
 * fica disponível para qualquer cliente MCP (Claude Desktop, Cursor, etc.)
 * via servidor HTTP/stdio.
 *
 * Uso (em app/Mcp/Servers/SupportServer.php):
 *
 *   $this->tools = [
 *       new PrismToolAdapter(new \App\Agents\Tools\ListCustomers),
 *       new PrismToolAdapter(new \App\Agents\Tools\SearchTickets),
 *   ];
 */
class PrismToolAdapter extends McpTool
{
    public function __construct(private readonly PrismTool $tool) {}

    public function name(): string
    {
        return $this->tool->name();
    }

    public function title(): string
    {
        // Default da Primitive usaria class_basename do adapter; aqui
        // queremos um título legível derivado do nome da tool Prism.
        return \Illuminate\Support\Str::headline($this->tool->name());
    }

    public function description(): string
    {
        return $this->tool->description();
    }

    /**
     * Mapeia os parâmetros do Prism\Tool (representação JSON-schema-like)
     * pra builders do Illuminate\JsonSchema que o Laravel MCP entende.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        $required = $this->tool->requiredParameters();

        return collect($this->tool->parametersAsArray())
            ->mapWithKeys(function (array $def, string $name) use ($schema, $required) {
                $builder = match ($def['type'] ?? 'string') {
                    'integer' => $schema->integer(),
                    'number' => $schema->number(),
                    'boolean' => $schema->boolean(),
                    'array' => $schema->array(),
                    'object' => $schema->object(),
                    default => $schema->string(),
                };

                if (! empty($def['description'])) {
                    $builder->description($def['description']);
                }

                if (in_array($name, $required, true)) {
                    $builder->required();
                }

                return [$name => $builder];
            })
            ->all();
    }

    /**
     * O Prism\Tool é callable via `handle(...$args)` (named-args
     * resolution interno). Aqui só repassamos o array do Request.
     */
    public function handle(Request $request): Response
    {
        $output = $this->tool->handle(...$request->all());

        return Response::text((string) $output);
    }
}
