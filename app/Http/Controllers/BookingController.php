<?php

namespace App\Http\Controllers;

use App\Models\Stadium;
use Illuminate\Http\Request;
use App\Models\BookingDetail;
use App\Models\BookingStadium;
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

    try {
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
                $timeSlotStadiumId = $timeSlotData->stadium_id;

                // Save to booking_stadium and get the ID
                $bookingStadium = BookingStadium::create([
                    'booking_status' => 'รอการชำระเงิน',  
                    'booking_date' => $validatedData['date'],
                    'users_id' => auth()->id(),
                    'time_slot_id' => $timeSlotId,  
                    'time_slot_stadium_id' => $timeSlotStadiumId,
                ]);

                // บันทึก id ลงใน session
                session(['booking_stadium_id' => $bookingStadium->id]);

                // Create BookingDetail
                BookingDetail::create([
                    'stadium_id' => $stadiumId,
                    'booking_stadium_id' => $bookingStadium->id,
                    'time_slot_id' => $timeSlotId,
                    'time_slot_stadium_id' => $timeSlotStadiumId,
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
        // ... (ข้อมูลเพิ่มเติมที่ต้องการ)
    ]);

    // เก็บ id การจองใน session
    session(['booking_stadium_id' => $bookingStadium->id]);

    return response()->json(['success' => true]);
}

public function showBookingDetails($bookingId)
{
    // Fetch the booking detail using the booking ID
    $bookingDetail = Booking::with(['user', 'stadiums.timeSlots'])->findOrFail($bookingId);

    // Get the booking details for the specified booking
    $bookingDetails = $bookingDetail->stadiums; // Assuming that `stadiums` is a relationship in the Booking model

    // Calculate total hours and total price if needed
    $totalHours = $bookingDetails->sum('booking_total_hour');
    $totalPrice = $bookingDetails->sum('booking_total_price');

    // Pass the variables to the view
    return view('your.view.name', [
        'bookingDetail' => $bookingDetail,
        'bookingDetails' => $bookingDetails,
        'totalHours' => $totalHours,
        'totalPrice' => $totalPrice,
    ]);
}




}



