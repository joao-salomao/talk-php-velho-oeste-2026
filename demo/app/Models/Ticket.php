<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    // Allowlist replicada na tool update_ticket_status (slide 17). Centralizar
    // aqui evita string-magia espalhada — a tool importa esta constante.
    public const STATUSES = ['open', 'in_progress', 'waiting_customer', 'resolved'];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $fillable = ['customer_id', 'subject', 'description', 'status', 'priority'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
