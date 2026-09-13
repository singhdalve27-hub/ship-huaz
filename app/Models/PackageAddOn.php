<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageAddOn extends Model
{
    protected $fillable = [
        'title',
        'description',
        'price',
        'status',
    ];
}
