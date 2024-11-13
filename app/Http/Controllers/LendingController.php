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
        $request->validate([
            'stadium_id' => 'required|exists:stadium,id',
            'booking_date' => 'required|date',
            'item_id' => 'required|array',
            'item_id.*' => 'exists:item,id',
            'borrow_quantity' => 'required|array',
            'borrow_quantity.*' => 'integer|min:0',
        ]);
    
        $bookingStadium = BookingStadium::where('users_id', auth()->id())
            ->where('booking_status', 'รอการชำระเงิน')
            ->latest()
            ->first();
    
            if (!$bookingStadium) {
                return redirect()->route('booking')->withErrors('กรุณาทำการจองก่อนยืมอุปกรณ์');
            }
            
    
        $borrow = Borrow::firstOrCreate(
            [
                'users_id' => auth()->id(),
                'booking_stadium_id' => $bookingStadium->id,
                'borrow_date' => $request->booking_date,
                'borrow_status' => 'รอการชำระเงิน',
            ]
        );
    
        $bookingDetails = BookingDetail::where('booking_stadium_id', $bookingStadium->id)
            ->where('booking_date', $request->booking_date)
            ->get();
    
        foreach ($request->item_id as $index => $itemId) {
            $borrowQuantity = $request->borrow_quantity[$index];
            
            if ($borrowQuantity == 0) {
                continue;
            }
        
            $item = Item::find($itemId);
            if (!$item) {
                return redirect()->back()->withErrors("Item not found: $itemId.");
            }
        
            // คำนวณยอดคงเหลือของอุปกรณ์โดยรวมการยืมในอดีตของผู้ใช้ในรหัสการจองนี้
            $existingBorrowedByUser = BorrowDetail::where('item_id', $itemId)
                ->where('users_id', auth()->id())
                ->where('borrow_id', $borrow->id) // เช็คแค่การยืมในรหัสการจองนี้
                ->sum('borrow_quantity');
            
            $remainingQuantity = $item->item_quantity - $existingBorrowedByUser;
        
            // ตรวจสอบว่าจำนวนที่ผู้ใช้ต้องการยืมรวมกันเกินยอดคงเหลือหรือไม่
            if ($borrowQuantity > $remainingQuantity) {
                return redirect()->back()->withErrors([
                    'message' => "จำนวนการยืมเกินยอดคงเหลือ ของ {$item->item_name} ยอดคงเหลือที่สามารถยืมได้ : $remainingQuantity",
                ]);
            }
            
        
            // โค้ดการบันทึกข้อมูลการยืม (BorrowDetail) ตามปกติ
            $timeSlotIds = [];
            foreach ($bookingDetails as $bookingDetail) {
                if ($bookingDetail->stadium_id == $request->stadium_id) {
                    $timeSlotIds[] = $bookingDetail->time_slot_id;
                }
            }
        
            $timeSlotIdsStr = implode(',', $timeSlotIds);
            $totalPrice = $item->price * $borrowQuantity;
        
            $existingDetail = BorrowDetail::where('borrow_id', $borrow->id)
                ->where('item_id', $itemId)
                ->where('stadium_id', $request->stadium_id)
                ->where('borrow_date', $request->booking_date)
                ->first();
        
            if ($existingDetail) {
                $existingTimeSlots = explode(',', $existingDetail->time_slot_id);
                $newTimeSlots = array_unique(array_merge($existingTimeSlots, $timeSlotIds));
                $existingDetail->time_slot_id = implode(',', $newTimeSlots);
                $existingDetail->borrow_quantity += $borrowQuantity;
                $existingDetail->borrow_total_price += $totalPrice;
                $existingDetail->save();
            } else {
                BorrowDetail::create([
                    'stadium_id' => $request->stadium_id,
                    'borrow_date' => $request->booking_date,
                    'time_slot_id' => $timeSlotIdsStr,
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
    
        return redirect()->back()->with('success', 'ยืมอุปกรณ์สำเร็จ');
    }
    
    


    
public function showBookingDetail($id)
{
    // ค้นหาข้อมูลการจอง
    $bookingDetail = BookingStadium::find($id);
     // ค้นหาข้อมูลการยืมอุปกรณ์ที่เกี่ยวข้อง
    $borrowingDetails = Borrow::where('booking_id', $id)->get();

    return view('bookingDetail', compact('bookingDetail', 'borrowingDetails'));
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


//ส่วนของแอดมิน ในช่วงของ ยืมคืนซ่อม
public function adminborrow(Request $request)
{
    // รับค่าสถานะจาก query string
    $status = $request->query('status');
    
    // สร้างคำสั่ง query
    $borrowDetailsQuery = BorrowDetail::with('borrow.user', 'item', 'item.itemType', 'stadium');
    
    // ถ้ามีการกำหนดสถานะ จะกรองตามสถานะ
    if ($status) {
        $borrowDetailsQuery->where('return_status', $status);
    }

    $borrowDetails = BorrowDetail::where('return_status', '!=', 'ยังไม่ตรวจสอบ')
    ->with(['borrow.bookingStadium', 'borrow.user', 'item', 'stadium', 'timeSlots'])
    ->get();


    return view('admin-borrow', compact('borrowDetails', 'status'));
}


public function approveBorrow($id)
{
    // หา borrow detail ตาม ID ที่ส่งมา
    $borrowDetail = BorrowDetail::find($id);

    // ตรวจสอบว่าเจอ borrow detail หรือไม่
    if ($borrowDetail) {
        // เปลี่ยนสถานะ return_status เป็น "ยืมแล้ว"
        $borrowDetail->return_status = 'ยืมแล้ว';
        $borrowDetail->save();

        // ส่งข้อความสถานะกลับไปยัง view
        return redirect()->back()->with('success', 'สถานะการยืมถูกเปลี่ยนเป็น "ยืมแล้ว" เรียบร้อย');
    } else {
        return redirect()->back()->with('error', 'ไม่พบข้อมูลการยืม');
    }
}


public function returnBorrow($id)
{
    // หา borrow detail ตาม ID ที่ส่งมา
    $borrowDetail = BorrowDetail::find($id);

     // ค้นหาข้อมูลอุปกรณ์ที่เกี่ยวข้อง
     $item = Item::findOrFail($borrowDetail->item_id); // สมมติว่า item_id คือคอลัมน์ที่เชื่อมโยงกับอุปกรณ์


    // ตรวจสอบว่าเจอ borrow detail หรือไม่
    if ($borrowDetail) {
        // เปลี่ยนสถานะ return_status เป็น "คืนแล้ว"
        $borrowDetail->return_status = 'คืนแล้ว';
        $borrowDetail->save();

        $item->item_quantity += $borrowDetail->borrow_quantity;
    $item->save();

        // ส่งข้อความสถานะกลับไปยัง view
        return redirect()->back()->with('success', 'สถานะการยืมถูกเปลี่ยนเป็น "คืนแล้ว" เรียบร้อย');
    } else {
        return redirect()->back()->with('error', 'ไม่พบข้อมูลการยืม');
    }
}


public function repairBorrow(Request $request, $id)
{
    // Validate the input for repair note
    $request->validate([
        'repair_note' => 'required|string|max:20', // Ensuring it's a string with a max length of 20
    ]);

    // Find the borrow detail by ID
    $borrowDetail = BorrowDetail::find($id);

    // Check if the borrow detail exists
    if ($borrowDetail) {
        // Change the return_status to "ซ่อม"
        $borrowDetail->return_status = 'ซ่อม';

        // Save the repair note
        $borrowDetail->repair_note = $request->repair_note;

        $borrowDetail->save();

        // Send success message back to the view
        return redirect()->back()->with('success', 'สถานะการยืมถูกเปลี่ยนเป็น "ซ่อม" เรียบร้อย');
    } else {
        return redirect()->back()->with('error', 'ไม่พบข้อมูลการยืม');
    }
}

public function repairComplete(Request $request, $id)
{
    // Validate the input for repair note
    $request->validate([
        'repair_note' => 'required|string|max:20', // Ensuring it's a string with a max length of 20
    ]);

    // Find the borrow detail by ID
    $borrowDetail = BorrowDetail::find($id);

     // ค้นหาข้อมูลอุปกรณ์ที่เกี่ยวข้อง
     $item = Item::findOrFail($borrowDetail->item_id); // สมมติว่า item_id คือคอลัมน์ที่เชื่อมโยงกับอุปกรณ์


    // Check if the borrow detail exists
    if ($borrowDetail) {
        // Change the return_status to "ซ่อมแล้ว"
        $borrowDetail->return_status = 'ซ่อมแล้ว';

        // Save the repair note
        $borrowDetail->repair_note = $request->repair_note;

        $borrowDetail->save();

        $item->item_quantity += $borrowDetail->borrow_quantity;
    $item->save();

        // Send success message back to the view
        return redirect()->back()->with('success', 'สถานะการยืมถูกเปลี่ยนเป็น "ซ่อมแล้ว" เรียบร้อย');
    } else {
        return redirect()->back()->with('error', 'ไม่พบข้อมูลการยืม');
    }
}


public function searchBorrow(Request $request)
{
    $query = BorrowDetail::with(['borrow', 'item', 'stadium', 'item.itemType']);

     // Apply booking stadium ID filter if provided
     if ($request->filled('booking_stadium_id')) {
        $query->whereHas('borrow', function ($q) use ($request) {
            // เช็คว่า booking_stadium_id ใน borrow ตรงกับค่าที่ค้นหา
            $q->where('booking_stadium_id', $request->booking_stadium_id);
        });
    }

    // Apply borrower name filter if provided
    if ($request->filled('fname')) {
        $query->whereHas('borrow.user', function ($q) use ($request) {
            $q->where('fname', 'like', '%' . $request->fname . '%');
        });
    
}

    // Apply borrow date filter if provided
    if ($request->filled('borrow_date')) {
        $query->where('borrow_date', $request->borrow_date);
    }

    // Apply status filter if provided
    if ($request->filled('status')) {
        $query->where('return_status', $request->status);
    }

    $borrowDetails = $query->get();
    
 
    return view('admin-borrow', compact('borrowDetails'));
}

public function repairUnable(Request $request, $id)
{
    $borrowDetail = BorrowDetail::findOrFail($id);
    $borrowDetail->return_status = 'ซ่อมไม่ได้';
    $borrowDetail->repair_note = $request->input('repair_note');
    $borrowDetail->save();

    return redirect()->back()->with('success', 'สถานะการซ่อมได้รับการอัปเดตเป็น ซ่อมไม่ได้');
}



}