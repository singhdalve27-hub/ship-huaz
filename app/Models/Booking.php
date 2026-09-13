<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'booking_ref',
        'event_type_id',
        'venue_package_id',
        'package_add_ons',
        'payment_option_id',
        'payment_account_number',
        'payment_transaction_ref',
        'guest_first_name',
        'guest_last_name',
        'guest_email',
        'guest_phone',
        'guest_count',
        'guest_request_notes',
        'time_slot',
        'exact_time',
        'booking_mode',
        'total_payment',
        'date',
        'status',
        'cancelled_by',
    ];

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
