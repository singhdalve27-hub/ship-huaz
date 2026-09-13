<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOption extends Model
{
    protected $fillable = [
        'payment',
        'number',
        'account',
        'description',
        'qr_code',
        'status',
    ];
}
