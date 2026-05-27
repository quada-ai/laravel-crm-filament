<?php

namespace VentureDrake\LaravelCrmFilament\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VentureDrake\LaravelCrm\Models\Invoice;

/**
 * Plugin-owned model for the `crm_invoice_payments` table written by the
 * Mark Paid action. Core CRM does not ship an InvoicePayment model — the
 * plugin defines its own so the action can persist a payment row alongside
 * the amount_paid / fully_paid_at fields on the parent Invoice. Hosts that
 * want to expose payment history should migrate the table by copying the
 * stub at database/migrations/.
 */
class InvoicePayment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('laravel-crm.db_table_prefix') . 'invoice_payments';
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
