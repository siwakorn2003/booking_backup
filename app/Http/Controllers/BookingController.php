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
    // ใช้ query string จาก URL ถ้าไม่มีก็ใช้วันที่ปัจจุบัน (เช่น 2024-11-12)
    $date = $request->query('date', date('Y-m-d'));

    // ดึงข้อมูลสนามทั้งหมดรวมถึง timeSlots และการจองในวันนี้จาก booking_detail
    $stadiums = Stadium::with(['timeSlots', 'details' => function($query) use ($date) {
    // กรองข้อมูลการจองที่เกี่ยวข้องกับวันที่จาก booking_detail
    $query->whereHas('bookingStadium', function($query) use ($date) {
        // กรองวันที่จาก booking_stadium โดยใช้ Carbon เพื่อแปลงวันที่
    $query->where('booking_date', Carbon::parse($date)); 

    });
    }])->get();

    // ค้นหาการจองที่มี users_id เป็น ID ของผู้ใช้ที่ล็อกอิน 
    $userBookings = BookingStadium::where('users_id', auth()->id())->get();

    // ดึงข้อมูลการจองในวันที่ที่ระบุจาก booking_detail ที่เกี่ยวข้องกับ booking_stadium
    $details = BookingDetail::with(['stadium', 'bookingStadium'])
        ->whereHas('bookingStadium', function($query) use ($date) {
            $query->where('booking_date', Carbon::parse($date));  // กรองวันที่จาก booking_stadium โดยใช้ Carbon เพื่อแปลงวันที่
        })
        ->whereNotNull('stadium_id') // กรอง stadium_id ที่ไม่เป็น null จาก booking_detail ก็คือต้องมีสนาม
        ->get();

    // ส่งข้อมูลไปยัง view
    return view('booking', compact('stadiums', 'date', 'details', 'userBookings')); // ส่งตัวแปร 'stadiums', 'date', 'details', และ 'userBookings' ไปยัง view 'booking'
}


public function store(Request $request) 
{
    // ตรวจสอบข้อมูลที่ส่งเข้ามาว่าถูกต้องหรือไม่ โดยต้องมีวันที่และช่องเวลาที่เลือก
    $validatedData = $request->validate([
        'date' => 'required|date', // ต้องมีวันที่ที่ถูกต้อง
        'timeSlots' => 'required|array' // ต้องมีช่วงเวลาที่เลือกเป็น array
    ]);

    // ตรวจสอบว่าผู้ใช้มีการจองที่มีสถานะ 'รอการชำระเงิน' อยู่แล้วหรือไม่
    $existingBooking = BookingStadium::where('users_id', auth()->id()) // ค้นหาการจองของผู้ใช้ที่มีสถานะ 'รอการชำระเงิน'
        ->where('booking_status', 'รอการชำระเงิน')
        ->first(); // หากมีการจองอยู่แล้ว จะคืนค่าข้อมูลการจองแรก

    // ถ้ามีการจองอยู่แล้ว ที่มีสถานะ 'รอการชำระเงิน' ใช้ bookingStadiumId เดิม ถ้าไม่มีก็สร้างใหม่
    if ($existingBooking) {
        // ถ้ามีการจองที่ยังรอการชำระเงิน จะใช้ ID เดิม
        $bookingStadiumId = $existingBooking->id;   // $existingBooking->id; เป็นการเข้าถึงค่า ID ของการจองสนาม ที่ได้จากการค้นหาข้อมูลในตาราง BookingStadium ในฐานข้อมูล
    } else {
        // ถ้าไม่มีการจองที่ยังรอการชำระเงิน ให้สร้างการจองใหม่
        $bookingStadium = BookingStadium::create([   // $bookingStadiumId ในบรรทัดนี้ใช้เพื่อเก็บ ID ของการจองสนาม
            'booking_status' => 'รอการชำระเงิน', // สถานะเริ่มต้นเป็น 'รอการชำระเงิน'
            'booking_date' => $validatedData['date'], // ใช้วันที่ที่ผู้ใช้เลือก
            'users_id' => auth()->id(), // ใช้ user_id ของผู้ใช้ที่ล็อกอิน
        ]);
        $bookingStadiumId = $bookingStadium->id; // เก็บ ID ของการจองใหม่
    }

        // วนลูปสำหรับแต่ละสนามที่ผู้ใช้เลือก
    foreach ($validatedData['timeSlots'] as $stadiumId => $timeSlots) {
        // ค้นหาข้อมูลสนามจาก database
        $stadium = Stadium::find($stadiumId);

        // สร้าง array สำหรับเก็บ time_slot_id ที่เลือก
        $newTimeSlotIds = [];
        // วนลูปช่วงเวลา (time slots) ที่เลือกสำหรับสนามนี้
        foreach ($timeSlots as $timeSlot) {
        // ค้นหา time_slot ที่ตรงกับช่วงเวลาที่ผู้ใช้เลือก
        $timeSlotData = \DB::table('time_slot')
            ->where('time_slot', $timeSlot) // ค้นหาตามเวลา
            ->where('stadium_id', $stadiumId) // ค้นหาตามสนาม
            ->first();
        // เก็บ time_slot_id ที่ได้จากการค้นหา
            $newTimeSlotIds[] = $timeSlotData->id;
        }

        // แปลง array ของ time_slot_id เป็น string โดยใช้เครื่องหมายจุลภาค (,) 
        $timeSlotIdsString = implode(',', $newTimeSlotIds);
        // คำนวณจำนวนชั่วโมงจากจำนวน time_slot ที่ผู้ใช้เลือก
        $totalHours = count($newTimeSlotIds);

        // ตรวจสอบว่ามีรายละเอียดการจองสำหรับสนามและวันที่นี้อยู่แล้วหรือไม่
        $existingBookingDetail = BookingDetail::where('stadium_id', $stadiumId)
            ->where('booking_date', $validatedData['date'])
            ->where('booking_stadium_id', $bookingStadiumId)
            ->first();

        // ถ้ามีรายละเอียดการจองแล้ว
        if ($existingBookingDetail) {
        // แยก time_slot_id ที่มีอยู่ในรายละเอียดการจอง
        $existingTimeSlotIds = explode(',', $existingBookingDetail->time_slot_id);
        // หาช่วงเวลาใหม่ที่ยังไม่ได้เพิ่มในรายละเอียดการจอง
        $newTimeSlotIdsToAdd = array_diff($newTimeSlotIds, $existingTimeSlotIds);
        // คำนวณจำนวนชั่วโมงที่เพิ่มขึ้น
        $newTotalHours = count($newTimeSlotIdsToAdd);

        // อัปเดตรายละเอียดการจอง โดยเพิ่มจำนวนชั่วโมงและราคา
        $existingBookingDetail->update([
            'booking_total_hour' => $existingBookingDetail->booking_total_hour + $newTotalHours, // อัปเดตจำนวนชั่วโมงที่จองโดยเพิ่มจำนวนชั่วโมงใหม่
            'booking_total_price' => $stadium->stadium_price * ($existingBookingDetail->booking_total_hour + $newTotalHours), // คำนวณราคาใหม่ โดยใช้จำนวนชั่วโมงที่อัปเดต
           // อัปเดตช่วงเวลา โดยเพิ่มช่วงเวลาที่ผู้ใช้เลือกใหม่
            'time_slot_id' => $existingBookingDetail->time_slot_id . ($newTimeSlotIdsToAdd ? ',' . implode(',', $newTimeSlotIdsToAdd) : ''), // implode ใช้สำหรับการแปลงอาร์เรย์ (array) ให้กลายเป็นสตริง
            ]);

        } else {
            // ถ้าไม่มีรายละเอียดการจองให้สร้างรายละเอียดใหม่
            BookingDetail::create([
                'stadium_id' => $stadiumId,
                'booking_stadium_id' => $bookingStadiumId, // ใช้ ID ของการจองที่สร้างไว้
                'booking_total_hour' => $totalHours, // จำนวนชั่วโมง
                'booking_total_price' => $stadium->stadium_price * $totalHours, // คำนวณราคา
                'booking_date' => $validatedData['date'], // วันที่ที่ผู้ใช้เลือก
                'users_id' => auth()->id(), // user_id ของผู้ใช้
                'time_slot_id' => $timeSlotIdsString, // ช่วงเวลาที่ผู้ใช้เลือก
            ]);
        }
    }

    // ส่งกลับผลลัพธ์เมื่อทำรายการสำเร็จ พร้อมข้อความสำเร็จ
    return response()->json([
        'success' => true, // แจ้งว่าการทำรายการสำเร็จ
        'booking_stadium_id' => $bookingStadiumId // ส่งกลับ ID ของการจองสนามที่สร้างหรืออัปเดต
    ]);
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

    // ตรวจสอบว่า $booking_stadium_id มีค่า 
    if ($booking_stadium_id) {
        // ดึงข้อมูลรายละเอียดการจองที่ตรงกับ $booking_stadium_id
        $bookingDetails = BookingDetail::where('booking_stadium_id', $booking_stadium_id)->get();

        // ตรวจสอบว่ามีข้อมูลการจองอยู่
        if ($bookingDetails->isNotEmpty()) {
            // แบ่งกลุ่มข้อมูลการจองโดยจัดกลุ่มตาม stadium_id และวันที่จอง (booking_date)
            $groupedBookingDetails = $bookingDetails->groupBy(function ($item) {
                // การใช้ (pipe)การใช้ (pipe) จะทำให้ข้อมูลที่มี stadium_id และ booking_date ซ้ำกันถูกจัดกลุ่มไว้ในกลุ่มเดียวกัน
                return $item->stadium_id . '|' . $item->booking_date; 
                // ในที่นี้ map ใช้เพื่อดำเนินการกับแต่ละกลุ่มที่เกิดจากการจัดกลุ่มโดย groupBy
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
            })->values(); // ทำให้ผลลัพธ์เป็น collection  ที่ได้จากผลลัพธ์ของ groupBy ให้กลับเป็น ค่าลำดับ 
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

    
public function confirmBooking($booking_stadium_id)
{
     // ค้นหาการจองสนามตาม booking_stadium_id
    $booking = BookingStadium::find($booking_stadium_id);
     // ตรวจสอบว่าการจองมีอยู่จริงและสถานะเป็น 'รอการชำระเงิน'
    if ($booking && $booking->booking_status === 'รอการชำระเงิน') {

     // ดึงข้อมูลการยืมที่เกี่ยวข้องกับการจองนี้จากตาราง Borrow
    $borrowing = Borrow::where('booking_stadium_id', $booking_stadium_id)->first();
    // ถ้าพบการยืมอุปกรณ์ที่เกี่ยวข้องกับการจอง
        if ($borrowing) {
            }

      // ถ้าการจองถูกต้อง ส่งไปยังหน้าชำระเงิน (paymentBooking) พร้อมส่งค่า booking_stadium_id
     // และแสดงข้อความ "การจองของคุณได้รับการยืนยันเรียบร้อยแล้ว"
        return redirect()->route('paymentBooking', ['booking_stadium_id' => $booking_stadium_id])
            ->with('success', 'การจองของคุณได้รับการยืนยันเรียบร้อยแล้ว');
    } else {
       // ถ้าการจองไม่พบ หรือสถานะไม่ตรงกับที่กำหนด (ไม่ใช่ 'รอการชำระเงิน')
      // ส่งกลับไปยังหน้ารายละเอียดการจอง (bookingDetail) พร้อมแสดงข้อผิดพลาด
        return redirect()->route('bookingDetail', ['id' => $booking_stadium_id])
            ->with('error', 'ไม่สามารถยืนยันการจองได้');
    }
}

//ส่วนลบยังทำไม่ได้
public function destroy($id)
{
    $booking = BookingStadium::find($id);
    if ($booking) {
        $booking->delete();
        return response()->json(['success' => true, 'message' => 'ลบการจองสำเร็จ']);
    }

    return response()->json(['success' => false, 'message' => 'ไม่พบการจอง']);
}




//รายการในประวัติที่ทำรายการยืมและจองไป เป็นหน้า history-detail
public function showHistoryDetail($booking_stadium_id)
{
    $userId = auth()->id(); // ดึง ID ของผู้ใช้ที่ล็อกอินอยู่
    $user = auth()->user(); // ดึงข้อมูลของผู้ใช้ที่ล็อกอินอยู่

    // ตรวจสอบบทบาทของผู้ใช้ (admin หรือ ผู้ใช้ทั่วไป) และดึงข้อมูลการจองสนาม
    if ($user->is_admin == 1) { //สำหรับแอดมิน จะแสดงรายการของทุกคน
         // ถ้าเป็นผู้ดูแลระบบ (admin), ผู้ดูแลสามารถดูการจองของทุกผู้ใช้ได้
        // ดึงข้อมูลการจองสนามตาม booking_stadium_id โดยใช้ Eloquent พร้อมกับข้อมูลของสนาม (stadium)
        $bookingStadium = BookingStadium::with('stadium')
            ->where('id', $booking_stadium_id)// ค้นหาการจองที่มี id ตรงกับ $booking_stadium_id
            ->first(); // คืนค่าผลลัพธ์แค่ 1 แถว
    } else { //ถ้าหากไม่ใช่แอดมิน จะแสดงเป็นรายการแค่ของตัวเอง
         // ถ้าเป็นผู้ใช้ทั่วไป (ไม่ใช่ admin), ผู้ใช้สามารถดูการจองของตนเองเท่านั้น
        // ดึงข้อมูลการจองที่เกี่ยวข้องกับผู้ใช้ที่ล็อกอินอยู่ (โดยใช้ userId)
        $bookingStadium = BookingStadium::with('stadium')
            ->where('id', $booking_stadium_id)// ค้นหาการจองที่มี id ตรงกับ $booking_stadium_id
            ->where('users_id', $userId)// ตรวจสอบว่าผู้ที่ทำการจองต้องตรงกับผู้ใช้นี้
            ->first();// คืนค่าผลลัพธ์แค่ 1 แถว
    }

    // ถ้าการจองไม่พบ หรือไม่สามารถเข้าถึงได้ ให้ย้อนกลับไปที่หน้าประวัติการจอง history booking พร้อมกับแสดงข้อความแจ้งเตือน
    if (!$bookingStadium) {
        return redirect()->route('history.booking')// เปลี่ยนเส้นทางไปหน้าประวัติการจอง
        ->with('error', 'ไม่พบข้อมูลการจอง');// ส่งข้อความข้อผิดพลาด
    }

    // ดึงข้อมูลรายละเอียดการจองจากตาราง booking_details โดยค้นหาการจองที่ตรงกับ $booking_stadium_id
    $bookingDetails = BookingDetail::where('booking_stadium_id', $booking_stadium_id)->get();
    // แปลงและจัดกลุ่มรายละเอียดการจองตาม stadium_id
    $groupedBookingDetails = $bookingDetails->map(function ($detail) {
         // แยก time_slot_id ที่เก็บเป็นคอมม่า (เช่น "1,2,3") ออกเป็น array ของ time slot IDs ใช้เพื่อระบุหรือแยกแยะข้อมูล
        $timeSlotIds = explode(',', $detail->time_slot_id);  
        
        // ดึงข้อมูล time slots ที่ตรงกับ time slot IDs ในฐานข้อมูลและเปลี่ยนเป็น array
        $timeSlots = \DB::table('time_slot')->whereIn('id', $timeSlotIds)->pluck('time_slot')->toArray();
        
        // รวมข้อมูล time slots ที่ดึงมาเป็น string และเก็บไว้ในตัวแปร $detail
        $detail->time_slots = implode(', ', $timeSlots);  
        return $detail; // ส่งกลับข้อมูลการจองพร้อมเวลาที่แยกออกมาแล้ว
        })->groupBy('stadium_id'); // จัดกลุ่มรายละเอียดการจองตาม stadium_id
        
        // ดึงรายละเอียดการยืมอุปกรณ์จาก Borrow ที่เชื่อมกับ booking_stadium_id
         $borrowingDetails = Borrow::where('booking_stadium_id', $booking_stadium_id)->get();

        // ดึงข้อมูลอุปกรณ์ทั้งหมดที่สามารถยืมได้จากตาราง Item
        $items = Item::all();

    // ส่งข้อมูลไปที่ view 'history-detail' โดยใช้ตัวแปร bookingStadium, bookingDetails, borrowingDetails, items และ groupedBookingDetails
    return view('history-detail', compact('bookingStadium', 'bookingDetails', 'borrowingDetails', 'items', 'groupedBookingDetails'));
}

//ในตอนที่แอดมินคอนเฟิร์มการชำระเงินแล้ว
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


//การปฏิเสธการชำระเงินของแอดมิน
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


// public function showBookingForm(Request $request)
// {
//     // กำหนดวันที่จาก URL หรือใช้วันที่ปัจจุบัน
//     $date = $request->get('date', \Carbon\Carbon::now()->format('Y-m-d'));

//     // ดึงข้อมูลสนามที่มีเวลาและรายละเอียด
//     $stadiums = Stadium::with('timeSlots')->get();

//     // ดึงข้อมูลการจองที่มีการจองในวันที่กำหนด
//     $bookingDetails = BookingDetail::where('booking_date', $request->get('date'))
//     ->with('bookingStadium') // ดึงข้อมูลที่เกี่ยวข้องกับสถานะการจอง
//     ->get();

//     // ส่งข้อมูลไปยัง View
//     return view('booking', compact('stadiums', 'date', 'bookingDetails'));  // ส่งค่า bookingDetails ไปด้วย
// }


// หน้าประวัติการจองและยืม นำข้อมูลมาแสดง
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
    //ตัดสถานะรอการชำระเงินออกไป เพราะว่าไม่จำเป็นต้องแสดง เนื่องจากสถานะรอการชำระเงิน ยังไม่ได้ทำรายการ ไม่จำเป็นต้องตรวจสอบในหน้านี้
    $query->where('booking_status', '!=', 'รอการชำระเงิน');

    // ดึงข้อมูลการจองทั้งหมดที่ผ่านการกรองตามเงื่อนไขต่าง ๆ
    $bookings = $query->get();

    // ส่งข้อมูลการจองไปที่หน้า view 'history-booking' เพื่อนำไปแสดงผล
    return view('history-booking', compact('bookings'));
}







}

