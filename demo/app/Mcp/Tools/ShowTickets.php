<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Resources\TicketsApp;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\RendersApp;
use Laravel\Mcp\Server\Tool;

#[Description('Render support tickets as an interactive board (status/priority badges, customer info). Optionally filter by status, keyword or customer_id.')]
#[RendersApp(resource: TicketsApp::class)]
class ShowTickets extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(Ticket::STATUSES)
                ->description('Filter by status: open, in_progress, waiting_customer, resolved.'),
            'keyword' => $schema->string()
                ->description('Free-text keyword matched against subject or description.'),
            'customer_id' => $schema->integer()
                ->description('Filter to a single customer (resolve via list_customers).'),
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
            ->latest('updated_at')
            ->limit(25)
            ->get(['id', 'customer_id', 'subject', 'description', 'status', 'priority', 'updated_at'])
            ->map(fn (Ticket $t) => [
                'id' => $t->id,
                'subject' => $t->subject,
                'description' => $t->description,
                'status' => $t->status,
                'priority' => $t->priority,
                'updated_at' => $t->updated_at?->toIso8601String(),
                'customer' => $t->customer ? [
                    'id' => $t->customer->id,
                    'name' => $t->customer->name,
                    'email' => $t->customer->email,
                ] : null,
            ])
            ->values();

        // O host empurra o `result.content[0].text` pra view via `ontoolresult`.
        // JSON puro: a view parseia e renderiza os cards.
        return Response::text($tickets->toJson());
    }
}
