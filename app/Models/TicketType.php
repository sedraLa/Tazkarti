<?php

namespace App\Models;

use App\Enums\TicketCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'category',
        'price',
        'currency',
        'quantity',
        'reserved_quantity',
        'sold_quantity',
        'max_per_order',
    ];

    protected function casts(): array
    {
        return [
            'category' => TicketCategory::class,
            'price' => 'decimal:2',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity - $this->sold_quantity;
    }
}
