<?php

namespace App\Models;

use App\Enums\TicketCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class TicketType extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (TicketType $ticketType) {
            static::invalidateAvailableQuantityCache($ticketType->id);
            Event::invalidateUpcomingCache();
        });

        static::deleted(function (TicketType $ticketType) {
            static::invalidateAvailableQuantityCache($ticketType->id);
            Event::invalidateUpcomingCache();
        });
    }

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
        if (! $this->id) {
            return (int) ($this->quantity - $this->reserved_quantity - $this->sold_quantity);
        }

        return (int) Cache::remember("ticket_types:{$this->id}:available_quantity", now()->addMinutes(10), function () {
            return (int) ($this->quantity - $this->reserved_quantity - $this->sold_quantity);
        });
    }

    public static function invalidateAvailableQuantityCache(int $ticketTypeId): void
    {
        Cache::forget("ticket_types:{$ticketTypeId}:available_quantity");
    }
}
