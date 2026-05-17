<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Disparado pela tool update_ticket_status (slide 17). Em produção
// listeners assíncronos cuidariam de auditoria, notificações ao customer,
// e reindex pra search. Aqui o evento existe pro demo mostrar o ponto
// de extensão sem precisar implementar listeners reais.
class TicketStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $previousStatus,
    ) {}
}
