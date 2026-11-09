<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'title',
        'message',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AnnouncementDelivery::class);
    }
}
