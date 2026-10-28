<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'number',
        'type',
        'customer_id',
        'supplier_id',
        'user_id',
        'issue_date',
        'due_date',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'amount_paid',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refreshPaymentStatus(): void
    {
        $paid = round((float) $this->payments()->sum('amount'), 2);
        $this->amount_paid = $paid;
        $this->status = $paid >= (float) $this->total
            ? 'paid'
            : ($this->due_date && $this->due_date->lt(today())
                ? 'overdue'
                : ($paid > 0 ? 'partial' : 'pending'));
        $this->save();
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status !== 'paid' && $this->due_date && $this->due_date->lt(today())) {
            return 'overdue';
        }

        return $this->status;
    }
}
