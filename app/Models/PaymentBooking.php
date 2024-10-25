<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentBooking extends Model
{
    use HasFactory;

    public $timestamps = false;


    // ถ้าต้องการระบุชื่อ table ที่ใช้งาน
    protected $table = 'payment_booking';

    // ระบุฟิลด์ที่สามารถทำการ mass assignment ได้
    protected $fillable = [
        'amount',
        'confirmation_pic',
        'booking_stadium_id',
        'borrow_id',
        'payer_name',
        'phone_number',
        'bank_name',
        'transfer_datetime',
    ];
}
