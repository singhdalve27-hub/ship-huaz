<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'booking_id',
        'user_id',
        'venue_package_id',
        'rating',
        'cleanliness_rating',
        'staff_rating',
        'facilities_rating',
        'value_rating',
        'comment',
        'status',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venuePackage(): BelongsTo
    {
        return $this->belongsTo(VenuePackage::class);
    }
}

