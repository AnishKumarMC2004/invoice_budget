<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'type',
        'description',
        'unit_price',
        'tax_rate',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'tax_rate' => 'decimal:2'];
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
