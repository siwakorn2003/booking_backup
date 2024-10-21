<?php

namespace App\Http\Controllers;

use App\Models\Stadium;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Borrow;
use App\Models\TimeSlot;
use App\Models\BorrowDetail; 
use App\Models\BookingDetail; 
use App\Models\BookingStadium; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LendingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['storeBorrow', 'borrowItem']); // ต้องเข้าสู่ระบบก่อน
    }

    // แสดงรายการอุปกรณ์
    public function index(Request $request)
{
    $search = $request->get('search');
    $itemTypeId = $request->get('item_type_id');

    $query = Item::query();

    if ($search) {
        $query->where('item_name', 'like', '%' . $search . '%');
    }

    if ($itemTypeId) {
        $query->where('item_type_id', $itemTypeId);
    }

    $items = $query->paginate(10); // Limit to 10 items per page

    $itemTypes = ItemType::all(); // ดึงประเภทอุปกรณ์

    $bookingDate = '2024-10-11'; // ตัวอย่างวันที่
    $bookingTime = '11:00'; // ตัวอย่างเวลา
    $stadiumId = 1; // ตัวอย่าง ID สนาม

    return view('lending.borrow-equipment', compact('items', 'itemTypes', 'bookingDate', 'bookingTime', 'stadiumId'));

}


    


    // แสดงฟอร์มเพิ่มอุปกรณ์
    public function addItem()
    {
        $itemTypes = ItemType::all();
        return view('lending.add-item', compact('itemTypes'));
    }

    // เก็บข้อมูลอุปกรณ์ใหม่
    public function storeItem(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:45',
            'item_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'item_type_id' => 'required|exists:item_type,id',
            'price' => 'required|integer',
            'item_quantity' => 'required|integer',
        ]);
    
        $itemType = ItemType::find($request->item_type_id);
        $lastItem = Item::where('item_code', 'LIKE', $itemType->type_code . '%')
            ->orderBy('item_code', 'desc')
            ->first();
    
        $lastCodeNumber = $lastItem ? intval(substr($lastItem->item_code, 2)) + 1 : 1;
        $newItemCode = $itemType->type_code . str_pad($lastCodeNumber, 3, '0', STR_PAD_LEFT);
    
        $imageName = null;
        if ($request->hasFile('item_picture')) {
            $imageName = time() . '.' . $request->item_picture->extension();
            $request->item_picture->storeAs('public/images', $imageName);
        }
    
        Item::create([
            'item_code' => $newItemCode,
            'item_name' => $request->item_name,
            'item_picture' => $imageName,
            'item_type_id' => $request->item_type_id,
            'price' => $request->price,
            'item_quantity' => $request->item_quantity,
        ]);
    
        return redirect()->route('lending.index')->with('success', 'เพิ่มอุปกรณ์สำเร็จ!');
    }

    // แสดงฟอร์มแก้ไขอุปกรณ์
    public function edit($id)
    {
        $item = Item::findOrFail($id);
        $itemTypes = ItemType::all();
        return view('lending.edit-item', compact('item', 'itemTypes'));
    }

    // อัปเดตอุปกรณ์
    public function update(Request $request, $id)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'item_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'item_type_id' => 'required|exists:item_type,id',
            'price' => 'required|numeric',
            'item_quantity' => 'required|integer|min:0',
        ]);

        $item = Item::findOrFail($id);
        $item->item_name = $request->input('item_name');

        if ($request->hasFile('item_picture')) {
            if ($item->item_picture && Storage::exists('public/images/' . $item->item_picture)) {
                Storage::delete('public/images/' . $item->item_picture);
            }
            $imagePath = $request->file('item_picture')->store('images', 'public');
            $item->item_picture = basename($imagePath);
        }

        $item->item_type_id = $request->input('item_type_id');
        $item->price = $request->input('price');
        $item->item_quantity = $request->input('item_quantity');
        $item->save();

        return redirect()->route('lending.index')->with('success', 'อัพเดตอุปกรณ์สำเร็จ!');
    }

    // ลบอุปกรณ์
    public function destroy($id)
    {
        $item = Item::findOrFail($id);

        if ($item->item_picture && Storage::exists('public/images/' . $item->item_picture)) {
            Storage::delete('public/images/' . $item->item_picture);
        }

        $item->delete();
        return redirect()->route('lending.index')->with('success', 'ลบอุปกรณ์สำเร็จ!');
    }
    public function borrowItem(Request $request)
{
    // ตรวจสอบข้อมูล
    $request->validate([
        'stadium_id' => 'required|exists:stadiums,id',
        'booking_date' => 'required|date',
        'time_slots' => 'required',
        'item_id' => 'required|exists:items,id',
        'borrow_quantity' => 'required|integer|min:1',
    ]);

    // สร้างรายการยืม
    BorrowDetail::create([
        'stadium_id' => $request->stadium_id,
        'booking_date' => $request->booking_date,
        'time_slot_id' => $request->time_slots,
        'item_id' => $request->item_id,
        'borrow_quantity' => $request->borrow_quantity,
        // เพิ่มฟิลด์อื่นๆ ตามที่จำเป็น
    ]);

    return redirect()->back()->with('success', 'ยืมอุปกรณ์สำเร็จแล้ว');
}


    // public function storeBorrow(Request $request)
    // {
    //     $request->validate([
    //         'item_id' => 'required|exists:items,id',
    //         'quantity' => 'required|integer|min:1',
    //         'booking_stadium_id' => 'required|exists:booking_stadium,id',
    //         'booking_date' => 'required|date',
    //         'booking_time' => 'required|string',
    //         'time_slots' => 'required|string',
    //     ]);
    
    //     // Create a new Borrow record
    //     $borrow = Borrow::create([
    //         'user_id' => auth()->id(),
    //         'booking_stadium_id' => $request->booking_stadium_id,
    //         'borrow_date' => now(), // Or any other logic for borrow date
    //         'booking_date' => $request->booking_date,
    //         'booking_time' => $request->booking_time,
    //         'time_slots' => $request->time_slots,
    //     ]);
    
    //     // Create BorrowDetail record
    //     BorrowDetail::create([
    //         'borrow_id' => $borrow->id,
    //         'item_id' => $request->item_id,
    //         'quantity' => $request->quantity,
    //     ]);
    
    //     // Update the item's quantity
    //     $item = Item::findOrFail($request->item_id);
    //     $item->item_quantity -= $request->quantity;
    //     $item->save();
    
    //     return redirect()->route('lending.index')->with('success', 'ยืมอุปกรณ์สำเร็จ!');
    // }
    

    // public function borrowEquipment(Request $request, $itemId)
    // {
    //     // Validate the data sent
    //     $validated = $request->validate([
    //         'borrow_quantity' => 'required|integer|min:1',
    //     ]);
    
    //     // Retrieve the logged-in user's information
    //     $userId = Auth::id();
    
    //     // Check if the user has a stadium booking
    //     $latestBooking = BookingStadium::where('users_id', $userId)
    //         ->where('booking_status', 'Booked') // Check status
    //         ->latest()
    //         ->first();
    
    //     if (!$latestBooking) {
    //         // If no booking exists, return error or redirect
    //         return redirect()->back()->with('error', 'You do not have a stadium booking yet.');
    //     }
    
    //     // Retrieve the booking and selected equipment information
    //     $bookingId = $latestBooking->id;
    //     $items = Item::all(); // Use the itemId passed
    
    //     // Extract additional data from the request if needed
    //     $bookingDate = $request->get('booking_date', $latestBooking->booking_date);
    //     $bookingTime = $request->get('booking_time', $latestBooking->booking_time);
    //     $stadiumId = $request->get('stadium_id', $latestBooking->stadium_id);
    
    //     // Assume you have this function to fetch time slots
    //     $time_slots = $this->getTimeSlots();
    
    //     return view('lending.borrow-equipment', compact('items', 'bookingId', 'bookingDate', 'bookingTime', 'stadiumId', 'time_slots'));
    // }
    


    

//     public function borrowItem(Request $request, $id)
// {
//     // ตรวจสอบว่ามี booking_id มาหรือไม่
//     $bookingId = $request->input('booking_id');
    
//     // ค้นหาการจองโดยใช้ booking_id
//     $booking = BookingStadium::with('bookingDetails')->findOrFail($bookingId);

//     $item = Item::findOrFail($id); // ค้นหา Item โดยใช้ $id
//     $stadiums = Stadium::with('timeSlots')->get(); // ดึงสนามทั้งหมด
//     $borrow_date = now()->format('Y-m-d'); // วันที่ยืมเป็นวันที่ปัจจุบัน

//     // ดึงข้อมูลการยืมล่าสุดที่เกี่ยวข้องกับผู้ใช้
//     $currentBorrow = Borrow::where('users_id', auth()->user()->id)
//         ->latest()
//         ->first();

//     // ตรวจสอบว่ามีการยืมและดึง booking_stadium_id
//     $booking_stadium_id = $currentBorrow ? $currentBorrow->booking_stadium_id : null;

//     // ดึงข้อมูล booking details ถ้ามี booking_stadium_id
//     $bookingDetails = $booking_stadium_id ? BookingDetail::where('booking_stadium_id', $booking_stadium_id)->get() : collect();

//     // ข้อมูลการจอง
//     $bookingDate = $booking->booking_date; // วันที่จอง
//     $stadiumId = $booking->stadium_id; // ID สนามจากการจอง
//     $timeSlots = $booking->time_slots; // ตัวอย่าง

//     return view('lending.borrow-item', compact('item', 'stadiums', 'borrow_date', 'booking_stadium_id', 'bookingDate', 'stadiumId', 'timeSlots', 'bookingDetails'));
// }



//     // เก็บข้อมูลการยืม
//     public function storeBorrow(Request $request)
// {
//     // dd($request->all());
    
//     $request->validate([
//         'item_id' => 'required|exists:item,id',
//         'borrow_date' => 'required|date',
//         'borrow_quantity' => 'required|integer|min:1',
//         'stadium_id' => 'required|exists:stadium,id',
//         'time_slot_id' => 'required|string', // เปลี่ยนเป็น string เพื่อค้นหา
//         'booking_stadium_id' => 'required|exists:booking_stadium,id', // ตรวจสอบว่ามีการส่ง booking_stadium_id มาด้วย
//     ]);

//     $item = Item::findOrFail($request->item_id);

//     // สมมุติว่าเวลาที่คุณส่งเป็นเวลา 11:00-12:00 และคุณต้องการดึง ID ของ time slot ที่ตรงกัน
//     $timeSlot = TimeSlot::where('time_slot', $request->time_slot_id)->first(); // ค้นหา time_slot ที่ตรงกับเวลา

//     if ($timeSlot) {
//         $timeSlotId = $timeSlot->id; // ได้ ID ของ time slot
//     } else {
//         return back()->withErrors(['error' => 'ไม่พบช่วงเวลาที่เลือก']);
//     }

//     try {
//         // บันทึกข้อมูลการยืมลงในตาราง borrow
//         $borrow = Borrow::create([
//             'users_id' => auth()->user()->id,
//             'item_id' => $request->item_id,
//             'borrow_date' => $request->borrow_date,
//             'borrow_status' => 'รอการชำระเงิน',
//             'time_slot_id' => $request->time_slot_id,
//             // 'time_slot_id' => $timeSlotId, // ใช้ ID ที่ถูกต้อง
//             'time_slot_stadium_id' => $request->stadium_id,
//             'booking_stadium_id' => $request->booking_stadium_id,
//         ]);

//         // ตรวจสอบว่าบันทึกสำเร็จหรือไม่
//         if ($borrow) {
//             // คำนวณรายละเอียดการยืม
//             $totalPrice = $item->price * $request->borrow_quantity; // คำนวณราคา
//             // บันทึกรายละเอียดการยืมลงใน borrow_detail
//             BorrowDetail::create([
//                 'borrow_id' => $borrow->id,
//                 'item_id' => $item->id,
//                 'item_item_type_id' => $item->item_type_id,
//                 'borrow_date' => $request->borrow_date,
//                 'borrow_quantity' => $request->borrow_quantity,
//                 'borrow_total_hour' => 1, // หรือค่าที่คุณต้องการ
//                 'borrow_total_price' => $totalPrice,
//                 'borrow_status' => 'รอการชำระเงิน',
//                 'users_id' => auth()->user()->id,
//                 'time_slot_id' => $timeSlotId, // ใช้ ID ที่ถูกต้อง
//                 'stadium_id' => $request->stadium_id,
//             ]);

//             // ส่งกลับไปยังหน้ารายละเอียด
//             return redirect()->route('booking.detail', ['id' => $request->booking_stadium_id])->with('success', 'ระบบเพิ่มรายการแล้ว โปรดตรวจสอบรายการอีกครั้งก่อนชำระเงิน');

//         } else {
//             return redirect()->back()->with('error', 'เกิดข้อผิดพลาด');
//         }
//     } catch (\Exception $e) {
//         return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
//     }
// }

    
//     public function borrowDetail()
//     {
//         // ดึงข้อมูลการยืมทั้งหมด (หรือปรับให้เหมาะสมตามที่คุณต้องการ)
//         $borrows = Borrow::with('item', 'user', 'details','stadium')->where('users_id', auth()->user()->id)->get(); // ตัวอย่างดึงข้อมูลตามผู้ใช้ที่ล็อกอิน

//           // สมมุติว่าเราจะส่งอุปกรณ์แรกจากการยืม
//     $item = $borrows->isNotEmpty() ? $borrows->first()->item : null;

//         return view('booking.detail', compact('borrows'));
        
//     }

//     public function destroyBorrow($id)
// {
//     $borrow = Borrow::findOrFail($id);
//     $borrow->delete(); // ลบรายการการยืม

//     return redirect()->route('booking.detail')->with('success', 'ลบรายการยืมสำเร็จ!');
// }

}    