<?php

namespace App\Http\Controllers;

use App\Models\Stadium;
use Illuminate\Http\Request;
use App\Models\BookingDetail;
use App\Models\BookingStadium;
use Illuminate\Support\Facades\Auth;

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
    
        try {
            $bookingDetailsArray = []; // สร้าง array เพื่อเก็บข้อมูลการจองทั้งหมด
    
            foreach ($validatedData['timeSlots'] as $stadiumId => $timeSlots) {
                foreach ($timeSlots as $timeSlot) {
                    // Retrieve the time slot data
                    $timeSlotData = \DB::table('time_slot')
                        ->where('time_slot', $timeSlot)
                        ->where('stadium_id', $stadiumId)
                        ->first(['id', 'stadium_id']); 
    
                    if (!$timeSlotData) {
                        return response()->json(['success' => false, 'message' => 'Invalid time or stadium.']);
                    }
    
                    $timeSlotId = $timeSlotData->id;
    
                    // Save to booking_stadium and get the ID
                    $bookingStadium = BookingStadium::create([
                        'booking_status' => 'รอการชำระเงิน',  
                        'booking_date' => $validatedData['date'],
                        'users_id' => auth()->id(),
                    ]);
    
                    // Create BookingDetail
                    BookingDetail::create([
                        'stadium_id' => $stadiumId,
                        'booking_stadium_id' => $bookingStadium->id,
                        'time_slot_id' => $timeSlotId,
                        'booking_total_hour' => 1, 
                        'booking_total_price' => 100, 
                        'booking_date' => $validatedData['date'],
                        'users_id' => auth()->id(),
                    ]);
                }
            }
    
            return response()->json(['success' => true]); 
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    public function confirmBooking(Request $request)
    {
        // บันทึกการจอง
        $bookingStadium = BookingStadium::create([
            'booking_status' => 'รอการชำระเงิน',  
            'booking_date' => $request->date,
            'users_id' => auth()->id(),
        ]);

        // เก็บ id การจองใน session
        session(['booking_stadium_id' => $bookingStadium->id]);

        return response()->json(['success' => true]);
    }

    public function showBookingDetail($bookingId)
    {
        // ดึงข้อมูลการจองตาม ID
        $bookingStadium = BookingStadium::findOrFail($bookingId); // เปลี่ยนจาก Booking เป็น BookingStadium
        // ดึงข้อมูลการจองรายละเอียด
        $bookingDetails = BookingDetail::where('booking_stadium_id', $bookingId)->get();

        // ตรวจสอบว่า bookingDetails มีข้อมูลหรือไม่
        if ($bookingDetails->isEmpty()) {
            return abort(404, 'No booking details found.');
        }

        // ดึงข้อมูลสนามและผู้ใช้
        $stadium = Stadium::find($bookingDetails[0]['stadium_id']);
        $user = Auth::user();
        $totalHours = $bookingDetails->sum('booking_total_hour');

        return view('bookingdetail', compact('bookingDetails', 'stadium', 'user', 'totalHours'));
    }
}
