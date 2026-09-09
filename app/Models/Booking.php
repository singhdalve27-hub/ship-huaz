<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class Booking extends Model
{
    protected $casts = [
        'package_add_ons' => 'array',
        'date'            => 'date:Y-m-d',
        'total_payment'   => 'decimal:2',
    ];

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function venuePackage()
    {
        return $this->belongsTo(VenuePackage::class);
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }
}
