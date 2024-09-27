<?php

namespace App\Http\Controllers;

use App\Models\Stadium;
use Illuminate\Http\Request;
use App\Models\BookingDetail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingController extends Controller
{

    public function index(Request $request)
    {
        $date = $request->query('date', date('Y-m-d'));
        $stadiums = Stadium::all();
        $bookings = BookingDetail::where('booking_date', $date)->get();
    
        return view('booking', compact('stadiums', 'bookings', 'date'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'date' => 'required|date',
            'timeSlots' => 'required|array'
        ]);

        foreach ($validatedData['timeSlots'] as $stadiumId => $timeSlots) {

            foreach ($timeSlots as $timeSlot) {
                // ดึง ID ของ time slot
               // ดึงข้อมูล time slot และ stadium_id ที่ตรงกับช่วงเวลาและสนาม
                $timeSlotData = \DB::table('time_slot')
                ->where('time_slot', $timeSlot)
                ->where('stadium_id', $stadiumId) // ตรวจสอบให้แน่ใจว่าข้อมูล stadium_id ตรงกันด้วย
                ->first(['id', 'stadium_id']); // ดึงข้อมูลทั้ง id และ stadium_id

                if (!$timeSlotData) {
                return response()->json(['success' => false, 'message' => 'เวลาหรือสนามนี้ไม่ถูกต้อง']);
                }

                // ใช้ค่า time_slot_id และ stadium_id ที่ดึงมาได้
                $timeSlotId = $timeSlotData->id;
                $timeSlotStadiumId = $timeSlotData->stadium_id;

                // บันทึกข้อมูลลงใน booking_stadium ด้วยค่าที่ถูกต้อง
                $bookingStadiumId = \DB::table('booking_stadium')->insertGetId([
                'booking_status' => 'รอการตรวจสอบ',  
                'booking_date' => $validatedData['date'],
                'users_id' => auth()->id(),
                'time_slot_id' => $timeSlotId,  
                'time_slot_stadium_id' => $timeSlotStadiumId,
                'created_at' => now(),
                'updated_at' => now(),
                ]);


                // สร้าง BookingDetail เพื่อเก็บรายละเอียดการจอง
                $bookingDetail = BookingDetail::create([
                    'stadium_id' => $stadiumId,
                    'booking_total_hour' => 1, // กำหนดจำนวนชั่วโมงการจอง
                    'booking_total_price' => 100, // กำหนดราคาการจอง
                    'booking_date' => $validatedData['date'],
                    'users_id' => auth()->id(),
                ]);
            }
        }

        return response()->json(['success' => true]); 
    }

}
