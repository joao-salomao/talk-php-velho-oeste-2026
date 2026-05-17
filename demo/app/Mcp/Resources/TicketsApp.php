<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\AppMeta;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;

#[Title('Tickets Board')]
#[Description('Interactive board that renders support tickets as cards with status, priority and customer info.')]
#[AppMeta]
class TicketsApp extends AppResource
{
    public function handle(Request $request): Response
    {
        return Response::view('mcp.tickets-app', [
            'title' => $this->title(),
        ]);
    }
}
