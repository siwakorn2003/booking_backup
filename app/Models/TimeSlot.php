<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $table = 'time_slot';
    public $timestamps = false;
    protected $fillable = [
        'time_slot',
        'stadium_id',
    ];

    // TimeSlot.php
public function stadium()
{
    return $this->belongsTo(Stadium::class);
}
}

