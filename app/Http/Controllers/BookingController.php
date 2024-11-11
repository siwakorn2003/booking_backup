<?php

namespace App\Http\Controllers;

use App\Models\Stadium;
use Illuminate\Http\Request;
use App\Models\BookingStadium;
use App\Models\BorrowDetail;
use App\Models\BookingDetail;
use App\Models\Borrow;
use App\Models\item;
use App\Models\TimeSlot;
use Carbon\Carbon;



use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index(Request $request)
{
    // รับวันที่จาก query string หรือกำหนดเป็นวันที่ปัจจุบันถ้าไม่มีการระบุ
    $date = $request->query('date', date('Y-m-d'));

    // ดึงข้อมูลสนามทั้งหมดรวมถึง timeSlots และการจองในวันนี้จาก booking_detail
    $stadiums = Stadium::with(['timeSlots', 'details' => function($query) use ($date) {
        // กรองข้อมูลการจองที่เกี่ยวข้องกับวันที่จาก booking_detail
        $query->whereHas('bookingStadium', function($query) use ($date) {
            $query->where('booking_date', Carbon::parse($date)); // กรองวันที่จาก booking_stadium
        });
    }])->get();

    // ดึงข้อมูลการจองของผู้ใช้ที่ล็อกออยู่ในวันนี้
    $userBookings = BookingStadium::where('users_id', auth()->id())->get();

    // ดึงข้อมูลการจองในวันที่ที่ระบุจาก booking_detail ที่เกี่ยวข้องกับ booking_stadium
    $details = BookingDetail::with(['stadium', 'bookingStadium'])
        ->whereHas('bookingStadium', function($query) use ($date) {
            $query->where('booking_date', Carbon::parse($date)); // กรองวันที่จาก booking_stadium
        })
        ->whereNotNull('stadium_id') // กรอง stadium_id ที่ไม่เป็น null จาก booking_detail ก็คือต้องมีเท่านั้น
        ->get();

    // ส่งข้อมูลไปยัง view
    return view('booking', compact('stadiums', 'date', 'details', 'userBookings'));
}


public function store(Request $request)
{
    // ตรวจสอบข้อมูลที่ส่งเข้ามาว่าถูกต้องหรือไม่ โดยต้องมีวันที่และช่องเวลาที่เลือก
    $validatedData = $request->validate([
        'date' => 'required|date',
        'timeSlots' => 'required|array'
    ]);

    try {
        // ตรวจสอบว่าผู้ใช้มีการจองที่มีสถานะ 'รอการชำระเงิน' อยู่แล้วหรือไม่
        $existingBooking = BookingStadium::where('users_id', auth()->id())
            ->where('booking_status', 'รอการชำระเงิน')
            ->first();

        // ถ้ามีการจองอยู่แล้ว ใช้ bookingStadiumId เดิม ถ้าไม่มีก็สร้างใหม่
        if ($existingBooking) {
            $bookingStadiumId = $existingBooking->id;
        } else {
            $bookingStadium = BookingStadium::create([
                'booking_status' => 'รอการชำระเงิน',
                'booking_date' => $validatedData['date'],
                'users_id' => auth()->id(),
            ]);
            $bookingStadiumId = $bookingStadium->id;
        }

        // ส่วนนี้เตรียมสำหรับตรวจสอบและแจ้งเตือนถ้ามีการจองซ้ำในช่วงเวลาเดียวกัน
        $conflictingTimeSlots = [];  // เก็บช่วงเวลาที่พบการจองซ้ำ

        // ตรวจสอบเวลาที่เลือกว่ามีผู้ใช้คนอื่นจองไว้แล้วในสถานะ 'รอการตรวจสอบ' หรือไม่
        foreach ($validatedData['timeSlots'] as $stadiumId => $timeSlots) {
            foreach ($timeSlots as $timeSlot) {
                // ตรวจสอบข้อมูลช่องเวลาว่าถูกต้องหรือไม่
                $timeSlotData = \DB::table('time_slot') 
                    ->where('time_slot', $timeSlot)
                    ->where('stadium_id', $stadiumId)
                    ->first();

                // ถ้าเวลาหรือสนามไม่ถูกต้อง ให้แจ้งข้อผิดพลาด
                if (!$timeSlotData) {
                    return response()->json(['success' => false, 'message' => 'เวลาหรือสนามไม่ถูกต้อง.']);
                }

                // ตรวจสอบว่ามีการจองของผู้ใช้คนอื่นในสถานะ 'รอการตรวจสอบ' หรือไม่
                $existingOtherUserBooking = BookingDetail::where('booking_date', $validatedData['date'])
                    ->where('stadium_id', $stadiumId)
                    ->where('time_slot_id', 'LIKE', '%' . $timeSlotData->id . '%')
                    ->whereHas('bookingStadium', function ($query) {
                        $query->where('booking_status', 'รอการตรวจสอบ');
                    })
                    ->exists();

                // ถ้ามีการจองซ้ำ เก็บเวลาที่ซ้ำในอาร์เรย์
                if ($existingOtherUserBooking) {
                    $conflictingTimeSlots[] = $timeSlot;
                }

                // ตรวจสอบการจองซ้ำของผู้ใช้คนเดิมที่สถานะ 'รอการชำระเงิน' และแจ้งข้อความ
                $existingUserBooking = BookingDetail::where('booking_date', $validatedData['date'])
                    ->where('users_id', auth()->id())
                    ->where('time_slot_id', 'LIKE', '%' . $timeSlotData->id . '%')
                    ->exists();

                if ($existingUserBooking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'คุณได้ทำรายการนี้ไปแล้ว ไม่สามารถทำซ้ำได้'
                    ]);
                }
            }
        }

        // ถ้ามีการจองซ้ำของผู้ใช้คนอื่นในช่วงเวลาที่ซ้ำ แสดงข้อความเตือน
        if (!empty($conflictingTimeSlots)) {
            $conflictingTimeSlotsText = implode(', ', $conflictingTimeSlots);
            return response()->json([
                'success' => false,
                'message' => 'มีผู้ใช้ท่านอื่นที่มีสถานะ "รอการตรวจสอบ" ในช่วงเวลา ' . $conflictingTimeSlotsText . ' ดังนั้นไม่สามารถจองซ้ำได้',
                'conflictingTimeSlots' => $conflictingTimeSlots
            ]);
        }

        // บันทึกหรืออัปเดตรายละเอียดการจองสำหรับแต่ละสนามและช่วงเวลา
        foreach ($validatedData['timeSlots'] as $stadiumId => $timeSlots) {
            $stadium = Stadium::find($stadiumId);
            if (!$stadium) {
                return response()->json(['success' => false, 'message' => 'สนามไม่ถูกต้อง.']);
            }

            $newTimeSlotIds = [];
            foreach ($timeSlots as $timeSlot) {
                $timeSlotData = \DB::table('time_slot')
                    ->where('time_slot', $timeSlot)
                    ->where('stadium_id', $stadiumId)
                    ->first();

                if (!$timeSlotData) {
                    return response()->json(['success' => false, 'message' => 'เวลาหรือสนามไม่ถูกต้อง.']);
                }

                $newTimeSlotIds[] = $timeSlotData->id;
            }

            // รวม time_slot_id เป็นสตริงเดียวเพื่อนำไปเก็บในฐานข้อมูล
            $timeSlotIdsString = implode(',', $newTimeSlotIds);
            $totalHours = count($newTimeSlotIds);

            // ตรวจสอบว่ามีรายละเอียดการจองอยู่แล้วหรือไม่ ถ้ามีจะอัปเดตข้อมูล
            $existingBookingDetail = BookingDetail::where('stadium_id', $stadiumId)
                ->where('booking_date', $validatedData['date'])
                ->where('booking_stadium_id', $bookingStadiumId)
                ->first();

            if ($existingBookingDetail) {
                // อัปเดตช่องเวลาและคำนวณชั่วโมงและราคาที่รวมใหม่
                $existingTimeSlotIds = explode(',', $existingBookingDetail->time_slot_id);
                $newTimeSlotIdsToAdd = array_diff($newTimeSlotIds, $existingTimeSlotIds);
                $newTotalHours = count($newTimeSlotIdsToAdd);

                $existingBookingDetail->update([
                    'booking_total_hour' => $existingBookingDetail->booking_total_hour + $newTotalHours,
                    'booking_total_price' => $stadium->stadium_price * ($existingBookingDetail->booking_total_hour + $newTotalHours),
                    'time_slot_id' => $existingBookingDetail->time_slot_id . ($newTimeSlotIdsToAdd ? ',' . implode(',', $newTimeSlotIdsToAdd) : ''),
                ]);
            } else {
                // สร้างรายละเอียดการจองใหม่
                BookingDetail::create([
                    'stadium_id' => $stadiumId,
                    'booking_stadium_id' => $bookingStadiumId,
                    'booking_total_hour' => $totalHours,
                    'booking_total_price' => $stadium->stadium_price * $totalHours,
                    'booking_date' => $validatedData['date'],
                    'users_id' => auth()->id(),
                    'time_slot_id' => $timeSlotIdsString,
                ]);
            }
        }

        // ส่งกลับผลลัพธ์เมื่อทำรายการสำเร็จ
        return response()->json([
            'success' => true,
            'booking_stadium_id' => $bookingStadiumId
        ]);
    } catch (\Exception $e) {
        // ส่งกลับข้อผิดพลาดหากเกิดข้อผิดพลาดในกระบวนการ
        return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
}






    

    
public function show()
{
    // ดึง user ID ของผู้ใช้ที่ล็อกอินอยู่
    $userId = auth()->id();

    // ค้นหาข้อมูลการจองล่าสุดของสนาม (BookingStadium) ที่มีสถานะ 'รอการชำระเงิน' สำหรับผู้ใช้งานปัจจุบัน
    $latestBookingStadium = BookingStadium::where('users_id', $userId)
        ->where('booking_status', 'รอการชำระเงิน') // เงื่อนไขเช็คสถานะการจอง
        ->latest() // จัดลำดับข้อมูลจากล่าสุดไปเก่าสุด
        ->first();

    // กำหนดค่า $booking_stadium_id เป็น ID ของการจองล่าสุดหรือ null หากไม่มีข้อมูล
    $booking_stadium_id = $latestBookingStadium ? $latestBookingStadium->id : null;
    $borrowingDetails = null; // ตั้งค่าเริ่มต้นให้เป็น null
    $groupedBookingDetails = collect(); // เริ่มต้นเป็นคอลเล็กชันว่าง หากไม่มีรายละเอียดการจอง
    $items = null; // ตั้งค่าเริ่มต้นให้เป็น null

    // ตรวจสอบว่า $booking_stadium_id มีค่า (หมายถึงมีการจอง)
    if ($booking_stadium_id) {
        // ดึงข้อมูลรายละเอียดการจองที่ตรงกับ $booking_stadium_id
        $bookingDetails = BookingDetail::where('booking_stadium_id', $booking_stadium_id)->get();

        // ตรวจสอบว่ามีข้อมูลการจองอยู่
        if ($bookingDetails->isNotEmpty()) {
            // แบ่งกลุ่มข้อมูลการจองโดยจัดกลุ่มตาม stadium_id และวันที่จอง (booking_date)
            $groupedBookingDetails = $bookingDetails->groupBy(function ($item) {
                return $item->stadium_id . '|' . $item->booking_date;
            })->map(function ($group) use ($latestBookingStadium) {
                // ดึงข้อมูล time slot ของการจองทั้งหมดในกลุ่ม
                $allTimeSlots = $group->flatMap(function ($detail) {
                    $timeSlotIds = explode(',', $detail->time_slot_id); // แปลง time_slot_id เป็น array
                    return \DB::table('time_slot')->whereIn('id', $timeSlotIds)->pluck('time_slot'); // ดึงข้อมูล time slot ที่ตรงกับ ID
                })->join(', '); // เชื่อมข้อมูล time slot เป็น string

                // กำหนดสถานะการจองจากการจองล่าสุด
                $bookingStatus = $latestBookingStadium->booking_status;

                // คืนค่ารายละเอียดการจองในรูปแบบ array
                return [
                    'id' => $group->first()->booking_stadium_id,
                    'stadium_id' => $group->first()->stadium_id,
                    'stadium_name' => $group->first()->stadium->stadium_name,
                    'booking_date' => $group->first()->booking_date,
                    'time_slots' => $allTimeSlots,
                    'total_price' => $group->sum('booking_total_price'), // รวมราคาทั้งหมดในกลุ่ม
                    'total_hours' => $group->sum('booking_total_hour'), // รวมชั่วโมงที่จองทั้งหมดในกลุ่ม
                    'booking_status' => $bookingStatus,
                ];
            })->values(); // ทำให้ผลลัพธ์เป็น collection
        }

        // ดึงข้อมูลรายละเอียดการยืมที่ตรงกับ $booking_stadium_id
        $borrowingDetails = Borrow::where('booking_stadium_id', $booking_stadium_id)->get();

        // ดึงข้อมูลรายการอุปกรณ์ที่มีทั้งหมดสำหรับให้ยืม
        $items = Item::all();

        // ส่งข้อมูลไปยัง view bookingDetail
        return view('bookingDetail', compact('groupedBookingDetails', 'bookingDetails', 'borrowingDetails', 'booking_stadium_id', 'items'));
    } else {
        // หากไม่มีการจองที่ตรงกับเงื่อนไข ให้แสดงข้อความแจ้งเตือนใน view
        $message = 'คุณยังไม่มีรายการจอง';
        return view('bookingDetail', compact('message', 'booking_stadium_id'));
    }
}

    


public function destroy($id)
{
    $booking = BookingStadium::find($id);
    if ($booking) {
        $booking->delete();
        return response()->json(['success' => true, 'message' => 'ลบการจองสำเร็จ']);
    }

    return response()->json(['success' => false, 'message' => 'ไม่พบการจอง']);
}


public function confirmBooking($booking_stadium_id)
{
    // ค้นหารายการจองที่ต้องการยืนยัน
    $booking = BookingStadium::find($booking_stadium_id);

    if ($booking && $booking->booking_status === 'รอการชำระเงิน') {
        // ไม่อัปเดตสถานะการจอง
        // $booking->update(['booking_status' => 'รอการตรวจสอบ']); // ลบหรือคอมเมนต์บรรทัดนี้

        // ดึงการยืมที่เกี่ยวข้อง
        $borrowing = Borrow::where('booking_stadium_id', $booking_stadium_id)->first();

        if ($borrowing) {
            // ไม่อัปเดตสถานะการยืม
            // $borrowing->update(['borrow_status' => 'รอการตรวจสอบ']); // ลบหรือคอมเมนต์บรรทัดนี้
        }

        // ส่งกลับไปยังหน้าชำระเงินพร้อมข้อมูลการจอง
        return redirect()->route('paymentBooking', ['booking_stadium_id' => $booking_stadium_id])
            ->with('success', 'การจองของคุณได้รับการยืนยันเรียบร้อยแล้ว');
    } else {
        // ถ้าไม่พบรายการจองหรือสถานะไม่ถูกต้อง ส่งกลับพร้อมข้อผิดพลาด
        return redirect()->route('bookingDetail', ['id' => $booking_stadium_id])
            ->with('error', 'ไม่สามารถยืนยันการจองได้');
    }
}


public function showHistoryDetail($booking_stadium_id)
{
    $userId = auth()->id(); // ดึง ID ของผู้ใช้ที่ล็อกอินอยู่
    $user = auth()->user(); // ดึงข้อมูลของผู้ใช้ที่ล็อกอินอยู่

    // ตรวจสอบบทบาทผู้ใช้และดึงข้อมูลการจองสนาม
    if ($user->is_admin == 1) {
        // ถ้าเป็นผู้ดูแลระบบ ให้ดึงข้อมูลการจองสนามตาม booking_stadium_id ได้ทุกผู้ใช้
        $bookingStadium = BookingStadium::with('stadium')
            ->where('id', $booking_stadium_id)
            ->first();
    } else {
        // ถ้าเป็นผู้ใช้ทั่วไป ให้ดึงข้อมูลการจองเฉพาะที่ผู้ใช้นั้นจองไว้เท่านั้น
        $bookingStadium = BookingStadium::with('stadium')
            ->where('id', $booking_stadium_id)
            ->where('users_id', $userId)
            ->first();
    }

    // ถ้าไม่พบข้อมูลการจองที่ระบุ ให้กลับไปหน้า history booking พร้อมกับแสดงข้อความแจ้งเตือน
    if (!$bookingStadium) {
        return redirect()->route('history.booking')->with('error', 'ไม่พบข้อมูลการจอง');
    }

    // ดึงข้อมูลรายละเอียดการจองจาก bookingDetails และแยก time_slot_id ที่เก็บเป็นคอมม่าออกมา
    $bookingDetails = BookingDetail::where('booking_stadium_id', $booking_stadium_id)->get();
    $groupedBookingDetails = $bookingDetails->map(function ($detail) {
        // แยก time_slot_id ที่เป็นคอมม่าออกเป็น array ของ time slot IDs
        $timeSlotIds = explode(',', $detail->time_slot_id);  
        
        // ดึงข้อมูล time slots ที่ตรงกับ time slot IDs ในฐานข้อมูลและเปลี่ยนเป็น array
        $timeSlots = \DB::table('time_slot')->whereIn('id', $timeSlotIds)->pluck('time_slot')->toArray();
        
        // รวม time slot เป็น string อีกครั้งและเก็บใน $detail
        $detail->time_slots = implode(', ', $timeSlots);  
        return $detail;
    })->groupBy('stadium_id'); // จัดกลุ่มตาม stadium_id

    // ดึงรายละเอียดการยืมอุปกรณ์จาก Borrow ที่เชื่อมกับ booking_stadium_id
    $borrowingDetails = Borrow::where('booking_stadium_id', $booking_stadium_id)->get();

    // ดึงข้อมูลอุปกรณ์ทั้งหมดที่สามารถยืมได้
    $items = Item::all();

    // ส่งข้อมูลไปที่ view 'history-detail' โดยใช้ตัวแปร bookingStadium, bookingDetails, borrowingDetails, items และ groupedBookingDetails
    return view('history-detail', compact('bookingStadium', 'bookingDetails', 'borrowingDetails', 'items', 'groupedBookingDetails'));
}


public function confirm($id)
{
    // ค้นหาการจองด้วย ID ที่ระบุ
    $booking = BookingStadium::findOrFail($id);
    
    // เปลี่ยนสถานะการจอง
    $booking->booking_status = 'ชำระเงินแล้ว';
    $booking->save();

   // เปลี่ยนสถานะการยืมที่เชื่อมโยง
   foreach ($booking->borrow as $borrowing) {
    // เปลี่ยนสถานะ borrow_detail ที่เชื่อมโยงกับ borrow และ booking_stadium นี้
    foreach ($borrowing->details as $detail) {
        // ตรวจสอบว่ารายละเอียดการยืมนี้เชื่อมโยงกับ booking_stadium นี้
        if ($detail->borrow->booking_stadium_id == $booking->id) {
            $detail->return_status = 'รอยืม';
            $detail->save();
        }
    }
}
    return redirect()->back()->with('success', 'ยืนยันการชำระเงินเรียบร้อยแล้ว');
}

public function reject(Request $request, $id)
{
    $booking = BookingStadium::findOrFail($id);
    $booking->booking_status = 'การชำระเงินถูกปฏิเสธ';
    $booking->reject_reason = $request->input('reject_reason');
    $booking->save();

    // ค้นหารายการยืมที่เกี่ยวข้อง
    $borrows = Borrow::where('booking_stadium_id', $id)->get();
    foreach ($borrows as $borrow) {
        // อัปเดตสถานะของรายการยืมเป็น 'การชำระเงินถูกปฏิเสธ'
        $borrow->borrow_status = 'การชำระเงินถูกปฏิเสธ';
        $borrow->save();

        // ค้นหารายละเอียดการยืม
        $borrowDetails = BorrowDetail::where('borrow_id', $borrow->id)->get();
        foreach ($borrowDetails as $detail) {
            // เพิ่มจำนวนที่ยืมกลับไปยังรายการอุปกรณ์
            $item = Item::findOrFail($detail->item_id);
            $item->item_quantity += $detail->borrow_quantity; // บวกจำนวนที่ยืมกลับ
            $item->save();
        }
    }

    return redirect()->back()->with('success', 'การปฏิเสธการชำระเงินสำเร็จแล้ว');
}


public function showBookingForm(Request $request)
{
    // กำหนดวันที่จาก URL หรือใช้วันที่ปัจจุบัน
    $date = $request->get('date', \Carbon\Carbon::now()->format('Y-m-d'));

    // ดึงข้อมูลสนามที่มีเวลาและรายละเอียด
    $stadiums = Stadium::with('timeSlots')->get();

    // ดึงข้อมูลการจองที่มีการจองในวันที่กำหนด
    $bookingDetails = BookingDetail::where('booking_date', $request->get('date'))
    ->with('bookingStadium') // ดึงข้อมูลที่เกี่ยวข้องกับสถานะการจอง
    ->get();

    // ส่งข้อมูลไปยัง View
    return view('booking', compact('stadiums', 'date', 'bookingDetails'));  // ส่งค่า bookingDetails ไปด้วย
}

 

public function historyShowbooking(Request $request)
{
    // เริ่มต้นสร้าง Query เพื่อค้นหาข้อมูลการจองจากตาราง BookingStadium
    $query = BookingStadium::query();

    // กรองข้อมูลการจองด้วย booking_stadium_id
    if ($request->has('booking_stadium_id') && $request->booking_stadium_id != '') {
        $query->where('id', $request->booking_stadium_id);
    }

    // กรองข้อมูลการจองตามชื่อผู้ชำระเงิน (payer_name) ที่ค้นหาจากตาราง payment_booking
    if ($request->has('fname') && $request->fname != '') {
        $query->whereHas('payment', function ($q) use ($request) {
            // ค้นหา payer_name โดยใช้ LIKE เพื่อตรวจสอบคำที่มีความคล้ายกัน
            $q->where('payer_name', 'like', '%' . $request->fname . '%');
        });
    }

    // กรองข้อมูลการจองตามวันที่การยืม (borrow_date) โดยตรวจสอบจาก created_at
    if ($request->has('borrow_date') && $request->borrow_date != '') {
        $query->whereDate('created_at', $request->borrow_date);
    }

    // กรองข้อมูลการจองตามสถานะ (booking_status) หากมีการระบุสถานะ
    if ($request->has('status') && $request->status != '') {
        $query->where('booking_status', $request->status);
    }

    // กรองข้อมูลโดยตัดสถานะ "รอการชำระเงิน" ออกจากผลลัพธ์
    $query->where('booking_status', '!=', 'รอการชำระเงิน');

    // ดึงข้อมูลการจองทั้งหมดที่ผ่านการกรองตามเงื่อนไขต่าง ๆ
    $bookings = $query->get();

    // ส่งข้อมูลการจองไปที่หน้า view 'history-booking' เพื่อนำไปแสดงผล
    return view('history-booking', compact('bookings'));
}







}

