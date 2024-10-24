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
use Illuminate\Support\Facades\DB; // เพิ่มบรรทัดนี้
use Illuminate\Support\Facades\Auth;

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

    return view('lending.index', compact('items', 'itemTypes', 'bookingDate', 'bookingTime', 'stadiumId'));

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
        // ตรวจสอบข้อมูลการยืม
        $request->validate([
            'stadium_id' => 'required|exists:stadium,id',
            'booking_date' => 'required|date',
            'item_id' => 'required|array',
            'item_id.*' => 'exists:item,id',
            'borrow_quantity' => 'required|array',
            'borrow_quantity.*' => 'integer|min:0',
        ]);
    
        // ดึงข้อมูลการจองจาก booking_stadium ล่าสุด
        $bookingStadium = BookingStadium::where('users_id', auth()->id())
            ->where('booking_status', 'รอการชำระเงิน')
            ->latest()
            ->first();
    
        if (!$bookingStadium) {
            return redirect()->back()->withErrors('การจองสนามไม่พบ');
        }
    
        // ตรวจสอบว่ามี borrow ที่มี booking_stadium_id ตรงกันหรือไม่
        $existingBorrow = Borrow::where('users_id', auth()->id())
            ->where('booking_stadium_id', $bookingStadium->id)
            ->first();
    
        if (!$existingBorrow) {
            // ถ้ายังไม่มีการบันทึกการยืม ให้สร้างใหม่
            $borrow = Borrow::create([
                'borrow_date' => $request->booking_date,
                'users_id' => auth()->id(),
                'booking_stadium_id' => $bookingStadium->id,
                'borrow_status' => 'รอการชำระเงิน',
            ]);
        } else {
            // ใช้ borrow_id เดิม
            $borrow = $existingBorrow;
        }
    
        // วนลูปสร้างรายการยืมสำหรับแต่ละ item
        foreach ($request->item_id as $index => $itemId) {
            $borrowQuantity = $request->borrow_quantity[$index];
    
            // ข้ามการบันทึกถ้าจำนวนการยืมเป็น 0
            if ($borrowQuantity == 0) {
                continue;
            }
    
            // ตรวจสอบว่า item มีอยู่หรือไม่
            $item = Item::find($itemId);
            if (!$item) {
                return redirect()->back()->withErrors("Item not found: $itemId.");
            }
    
            // วนลูปตาม bookingDetails เพื่อบันทึก time slots ทั้งหมดที่จองไว้
            $bookingDetails = BookingDetail::where('booking_stadium_id', $bookingStadium->id)
                ->where('booking_date', $request->booking_date)
                ->get();
    
            foreach ($bookingDetails as $bookingDetail) {
                $timeSlotId = $bookingDetail->time_slot_id;
    
                // คำนวณราคายืมรวม
                $totalPrice = $item->price * $borrowQuantity; // คำนวณราคาเพียงครั้งเดียว
    
                // ตรวจสอบว่ามีการยืมรายการเดียวกันหรือไม่
                $existingDetail = BorrowDetail::where('borrow_id', $borrow->id)
                    ->where('item_id', $itemId)
                    ->where('stadium_id', $bookingDetail->stadium_id)
                    ->where('borrow_date', $bookingDetail->booking_date)
                    ->where('time_slot_id', $timeSlotId)
                    ->first();
    
                if ($existingDetail) {
                    // ถ้ามีรายการอยู่แล้ว เพิ่มจำนวนและราคา
                    $existingDetail->borrow_quantity += $borrowQuantity; // เพิ่มจำนวน
                    $existingDetail->borrow_total_price += $totalPrice; // เพิ่มราคา
                    $existingDetail->save();
                } else {
                    // ถ้ายังไม่มีรายการใหม่ ให้บันทึก
                    BorrowDetail::create([
                        'stadium_id' => $bookingDetail->stadium_id,
                        'borrow_date' => $bookingDetail->booking_date,
                        'time_slot_id' => $timeSlotId,
                        'item_id' => $itemId,
                        'borrow_quantity' => $borrowQuantity,
                        'borrow_total_price' => $totalPrice,
                        'borrow_total_hour' => 0,
                        'item_item_type_id' => $item->item_type_id,    
                        'borrow_id' => $borrow->id,
                        'users_id' => auth()->id(),
                    ]);
                }
            }
        }
    
        return redirect()->back()->with('success', 'ยืมอุปกรณ์สำเร็จ');
    }
    



    
public function showBookingDetail($bookingId)
{
    // ดึงข้อมูลอุปกรณ์ทั้งหมดจากตาราง Item
    $items = Item::all(); // หรือคุณสามารถปรับ Query ตามที่ต้องการ

    $booking = BookingStadium::with('details')->findOrFail($bookingId);
   
    // ส่งตัวแปร $items ไปยัง View 'bookingDetail'
    return view('bookingDetail', compact('items', 'booking'));


}

public function destroyBorrow($id)
{
    // ค้นหาการยืมตาม ID
    $borrow = Borrow::findOrFail($id);
    
    // ลบรายละเอียดการยืมที่เกี่ยวข้อง
    $borrowDetails = BorrowDetail::where('borrow_id', $borrow->id);
    $borrowDetails->delete();

    // ลบการยืม
    $borrow->delete();

    return response()->json(['success' => 'ลบการยืมสำเร็จ']);
}



}