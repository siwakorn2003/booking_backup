<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stadium;
use App\Models\BookingStadium;

class BookingController extends Controller
{
    public function store(Request $request)
{
    $date = Carbon::parse($request->input('date'));
    $today = Carbon::now();
    $maxDate = $today->copy()->addDays(7);

    if ($date < $today || $date > $maxDate) {
        return redirect()->back()->withErrors('วันที่ที่เลือกต้องอยู่ภายใน 7 วันจากวันนี้');
    }

    // Proceed with booking logic
}
    public function index(Request $request)
    {
        // รับวันที่จาก query string หรือใช้วันที่ปัจจุบัน
        $date = $request->query('date', date('Y-m-d'));

        // ดึงข้อมูลสนามและการจอง
        $stadiums = Stadium::all();
        $bookings = BookingStadium::where('booking_date', $date)->get();

        // ส่งข้อมูลไปยัง view
        return view('booking', compact('stadiums', 'bookings', 'date'));
    }
    public function confirmation(Request $request)
{
    // ตัวอย่างการคำนวณ total price
    $bookings = BookingStadium::where('user_id', auth()->id())->get();
    $totalPrice = $bookings->sum('price'); // สมมุติว่า 'price' คือฟิลด์ที่เก็บราคาการจอง

    return view('confirmation', compact('bookings', 'totalPrice'));
}


}