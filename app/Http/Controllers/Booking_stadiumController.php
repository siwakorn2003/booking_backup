<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;

class Booking_stadiumController extends Controller
{
    // Show booking form
    public function showForm()
    {
        return view('booking_stadium.form');
    }

    // Handle booking submission
    public function bookField(Request $request)
    {
        $request->validate([
            'field' => 'required|string',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        Booking::create([
            'field' => $request->field,
            'name' => $request->name,
            'phone' => $request->phone,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()->back()->with('success', 'Booking successful!');
    }
}
