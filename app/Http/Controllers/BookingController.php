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
        $bookings = BookingDetail::where('booking_date', $date)->get(); // เปลี่ยนจาก BookingStadium เป็น BookingDetail
    
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
                $timeSlotId = \DB::table('time_slot')
                    ->where('time_slot', $timeSlot) // ช่วงเวลาที่ส่งมาจากฟอร์ม
                    ->value('id'); // ดึง ID ของช่วงเวลานั้น
    
                if (!$timeSlotId) {
                    return response()->json(['success' => false, 'message' => 'เวลานี้ไม่ถูกต้อง']);
                }
    
                // ตรวจสอบการจองซ้ำใน booking_detail แทน booking_stadium
                $existingBooking = BookingDetail::where('booking_date', $validatedData['date'])
                    ->where('time_slot_id', $timeSlotId)  // ต้องเพิ่มคอลัมน์ time_slot_id ใน booking_detail ถ้ายังไม่มี
                    ->exists();
    
                if ($existingBooking) {
                    return response()->json(['success' => false, 'message' => 'เวลานี้ถูกจองแล้ว']);
                }
    
                // บันทึกการจองใน booking_detail แทน booking_stadium
                BookingDetail::create([
                    'stadium_id' => $stadiumId,
                    'booking_stadium_id' => 1, // กำหนด booking_stadium_id ถ้าจำเป็น ถ้าไม่ใช้ก็ปรับโครงสร้าง
                    'booking_total_hour' => 1, // กำหนดจำนวนชั่วโมงการจอง
                    'booking_total_price' => 100, // กำหนดราคาการจอง
                    'booking_date' => $validatedData['date'],
                    'booking_status' => 'รอการตรวจสอบ',
                    'users_id' => auth()->id(),
                ]);
            }
        }
    
        return response()->json(['success' => true]); 
    }
    
    
}