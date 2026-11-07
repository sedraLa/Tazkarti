<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'venue_id',
        'title',
        'description',
        'event_type',
        'start_at',
        'end_at',
        'status',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'is_featured' => 'boolean',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::PUBLISHED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_at', '>=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeCity(Builder $query, ?string $city): Builder
    {
        return $city ? $query->whereHas('venue', fn($q) => $q->where('city', $city)) : $query;
    }

    public function scopeEventType(Builder $query, ?string $eventType): Builder
    {
        return $eventType ? $query->where('event_type', $eventType) : $query;
    }

    public function scopeDate(Builder $query, ?string $date, ?string $toDate = null): Builder
    {
        if ($date && $toDate) {
            return $query->whereBetween('start_at', [$date, $toDate]);
        }

        return $date ? $query->whereDate('start_at', $date) : $query;
    }
}
