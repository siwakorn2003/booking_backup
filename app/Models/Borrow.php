<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrow extends Model
{
    use HasFactory;
    public $timestamps = true;

    protected $table = 'borrow';
    // protected $guarded = []; // ทำให้ทุกคอลัมน์สามารถ fill ได้ การลบ protected $guarded และใช้ protected $fillable แทน จะช่วยป้องกันการอัพเดทข้อมูลในคอลัมน์ที่ไม่ต้องการ เช่น id หรือคอลัมน์อื่นที่ไม่ต้องการให้ถูก mass-assigned
    protected $fillable = [
        'borrow_date',
        'borrow_status',
        'users_id',
        'booking_stadium_id', // คอลัมน์ใหม่ที่เพิ่มเข้ามา
    ];

    // สัมพันธ์กับตาราง Item (ถ้ามีความสัมพันธ์แบบอื่น)
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // สัมพันธ์กับตาราง Users
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }

    // สัมพันธ์กับตาราง BorrowDetail
    public function details()
    {
        return $this->hasMany(BorrowDetail::class, 'borrow_id'); 
    }
}

class BorrowDetail extends Model
{
    use HasFactory;
    public $timestamps = false; // ไม่มี timestamp ในฐานข้อมูล

    protected $table = 'borrow_detail';
    protected $guarded = [];
    protected $fillable = [
        'item_id',
        'item_item_type_id',
        'borrow_id',
        'borrow_date',
        'borrow_quantity',
        'borrow_total_hour',
        'borrow_total_price',
        'borrow_status',
        'users_id',
        'time_slot_id',
        'stadium_id',
    ];

    // สัมพันธ์กับตาราง Item
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // สัมพันธ์กับตาราง Borrow
    public function borrow()
    {
        return $this->belongsTo(Borrow::class, 'borrow_id');
    }

    // สัมพันธ์กับตาราง TimeSlot
    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }

    // สัมพันธ์กับตาราง Stadium
    public function stadium()
    {
        return $this->belongsTo(Stadium::class, 'stadium_id');
    }

    // สัมพันธ์กับตาราง Users
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
