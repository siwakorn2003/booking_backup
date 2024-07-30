<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // Define the fillable attributes
    protected $fillable = [
        'field',
        'name',
        'phone',
        'date',
        'start_time',
        'end_time',
    ];
}
