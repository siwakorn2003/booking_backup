<?php

namespace App\Http\Controllers;

use App\Models\Stadium;
use Illuminate\Http\Request;
use App\Models\BookingStadium;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingController extends Controller
{
    // public function index(Request $request)
    // {
    //     $date = $request->query('date', date('Y-m-d'));
    //     $stadiums = Stadium::all();
    //     $bookings = BookingStadium::where('booking_date', $date)->get();

    //     return view('booking', compact('stadiums', 'bookings', 'date'));
    // }

    // public function store(Request $request)
    // {
    //     // ตรวจสอบข้อมูลที่ส่งมา
    //     $validatedData = $request->validate([
    //         'date' => 'required|date',
    //         'timeSlots' => 'required|array',
    //         'timeSlots.*' => 'required|integer|exists:time_slots,id'
    //     ]);

    //     $userId = Auth::id();
    //     $date = $validatedData['date'];
    //     $timeSlots = $validatedData['timeSlots'];

    //     // บันทึกการจองลงในฐานข้อมูล
    //     foreach ($timeSlots as $timeSlotId) {
    //         BookingStadium::create([
    //             'booking_date' => $date,
    //             'booking_status' => 'รอการตรวจสอบ', // สถานะเริ่มต้น
    //             'user_id' => $userId,
    //             'time_slot_id' => $timeSlotId
    //         ]);
    //     }

    //     return response()->json(['success' => true]);
    // }
    public function index(Request $request)
    {
        $date = $request->query('date', date('Y-m-d'));
        $stadiums = Stadium::all();
        $bookings = BookingStadium::where('booking_date', $date)->get();

        return view('booking', compact('stadiums', 'bookings', 'date'));
    }

    // public function store(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'date' => 'required|date',
    //         'timeSlots' => 'required|array',
    //         'timeSlots.*' => 'required|string' // แก้ไขจาก 'array' เป็น 'string'
    //     ]);
    
    //     // ลูปผ่านแต่ละ time slot ที่เลือก
    //     foreach ($validatedData['timeSlots'] as $stadiumId => $timeSlots) {
    //         foreach ($timeSlots as $timeSlot) {
    //             BookingStadium::create([
    //                 'booking_date' => $validatedData['date'],
    //                 'booking_status' => 0, // 0 = รอการตรวจสอบ
    //                 'user_id' => Auth::id(),
    //                 'time_slot_id' => $timeSlot
    //             ]);
    //         }
    //     }
    
    //     return response()->json(['success' => true]);
    // }
    public function store(Request $request)
{
    $validatedData = $request->validate([
        'date' => 'required|date',
        'timeSlots' => 'required|array'
    ]);

    foreach ($validatedData['timeSlots'] as $stadiumId => $timeSlots) {
        foreach ($timeSlots as $timeSlot) {
            BookingStadium::create([
                'booking_date' => $validatedData['date'],
                'booking_status' => 0, // รอการตรวจสอบ
                'user_id' => auth()->id(),
                'time_slot_id' => $timeSlot
            ]);
        }
    }

    return response()->json(['success' => true]);
}

}

