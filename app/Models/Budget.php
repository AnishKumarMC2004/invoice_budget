<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    protected $fillable = [
        'name',
        'period',
        'year',
        'month',
        'amount',
        'category',
        'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
