<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

class Event extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (Event $event) {
            static::invalidateUpcomingCache();
        });

        static::deleted(function (Event $event) {
            static::invalidateUpcomingCache();
        });
    }

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

    public function announcements(): HasMany
    {
        return $this->hasMany(EventAnnouncement::class);
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

    public static function getUpcomingCached(?string $city = null, ?string $eventType = null, int $perPage = 15, int $page = 1)
    {
        $version = (int) Cache::get('events:upcoming:version', 1);
        $cityKey = $city !== null && $city !== '' ? $city : 'all';
        $typeKey = $eventType !== null && $eventType !== '' ? $eventType : 'all';

        $key = "events:upcoming:v{$version}:city:{$cityKey}:type:{$typeKey}:per_page:{$perPage}:page:{$page}";

        return Cache::remember($key, now()->addMinutes(10), function () use ($city, $eventType, $perPage, $page) {
            return static::published()
                ->upcoming()
                ->city($city)
                ->eventType($eventType)
                ->with(['venue', 'organizer', 'images'])
                ->orderBy('start_at')
                ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    public static function invalidateUpcomingCache(): void
    {
        Cache::put('events:upcoming:version', ((int) Cache::get('events:upcoming:version', 1)) + 1, now()->addDays(30));
    }
}
