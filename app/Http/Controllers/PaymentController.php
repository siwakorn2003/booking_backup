<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function processPayment(Request $request)
{
    // Validate the request
    $request->validate([
        'booking_code' => 'required|string',
        'payer_name' => 'required|string',
        'phone_number' => 'required|string',
        'payment_date_time' => 'required|date',
        'amount' => 'required|numeric',
        'slip_upload' => 'required|file|mimes:jpg,png,jpeg,pdf|max:2048', // กำหนดขนาดไฟล์สูงสุดที่อัปโหลด
    ]);

    // Process the payment (save to database, send notification, etc.)

    return redirect()->route('payment')->with('success', 'การชำระเงินของคุณได้รับการบันทึกแล้ว!');
}
public function showPaymentForm($booking_stadium_id)
{
    // คุณอาจต้องการดึงข้อมูลการจองและส่งไปยังหน้าแจ้งชำระเงิน
    return view('paymentBooking', compact('booking_stadium_id'));
}

}
