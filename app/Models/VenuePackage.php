<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

class VenuePackage extends Model
{
    protected $fillable = [
        'event_type_id',
        'title',
        'description',
        'image',
        'guests',
        'price',
        'price_morning',
        'price_afternoon',
        'price_night',
        'price_fullday',
        'price_visitor',
        'status',
    ];
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Idinagdag ang relationship para sa EventType
    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Awtomatikong i-map ang image URL. Kung ang naka-save sa database ay ang mga default un-synced
     * hashes o kulang sa storage, i-route ito sa permanenteng committed images para maiwasan ang 404.
     */
    protected function image(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return '/images/venue.jpg';
                }
                if (str_contains($value, 'XmTOpOEzc8Q71BziIlsEPiIcOn36151otlKZ9b5I')) {
                    return '/images/venue.jpg';
                }
                if (str_contains($value, '6pdOrmIILX1jfPKuzGK7Wes1hNF0L0hzLfIWkjBF')) {
                    return '/images/venue2.jpg';
                }
                if (str_contains($value, '4wdCZSeyN6xugrJkZucQ8gY32U519AtZoR7gGyOJ')) {
                    return '/images/dining.jpg';
                }
                return $value;
            }
        );
    }
}