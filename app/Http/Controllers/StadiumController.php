<?php

namespace App\Http\Controllers;

use App\Models\Stadium;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StadiumController extends Controller
{
    // แสดงรายการสนามทั้งหมดพร้อมช่วงเวลาที่เกี่ยวข้อง
    public function index() {
        // $stadiums เป็นตัวแปรที่เก็บข้อมูลสนามทั้งหมดพร้อมช่วงเวลาที่ดึงจากฐานข้อมูล
        $stadiums = Stadium::with('timeSlots')->get();
        return view('stadium.index', compact('stadiums')); // ส่งตัวแปร $stadiums ไปแสดงในหน้า stadium.index
    }

    // แสดงข้อมูลสนามทั้งหมดพร้อมช่วงเวลา (ใช้ในหน้าแสดงข้อมูลโดยละเอียด)
    public function show() {
        // $stadiums เป็นตัวแปรที่เก็บข้อมูลสนามทั้งหมดพร้อมช่วงเวลาที่ดึงจากฐานข้อมูล
        $stadiums = Stadium::with('timeSlots')->get();
        return view('stadium.show', compact('stadiums')); // ส่งตัวแปร $stadiums ไปแสดงในหน้า stadium.show
    }

    // แสดงฟอร์มสำหรับสร้างสนามใหม่
    public function create() {
        // $time_slots เป็นตัวแปรที่เก็บข้อมูลช่วงเวลาทั้งหมดเพื่อใช้ในหน้า create
        $time_slots = TimeSlot::all();
        return view('stadium.create', compact('time_slots')); // ส่งตัวแปร $time_slots ไปแสดงในฟอร์ม create
    }

    // บันทึกข้อมูลสนามใหม่พร้อมช่วงเวลา
    public function store(Request $request)
    {
        // ตรวจสอบข้อมูลที่รับเข้ามาใน $request
        $request->validate([
            'stadium_name' => 'required|string|max:255', // ชื่อสนาม
            'stadium_price' => 'required|numeric', // ราคาสนาม
            'stadium_status' => 'required|string', // สถานะของสนาม
            'start_time' => 'required|array', // ช่วงเวลาเริ่มต้น เป็น array
            'end_time' => 'required|array', // ช่วงเวลาสิ้นสุด เป็น array
            'start_time.*' => 'required|date_format:H:i', // ช่วงเวลาเริ่มต้นในรูปแบบ HH:MM
            'end_time.*' => 'required|date_format:H:i|after:start_time.*' // ช่วงเวลาสิ้นสุดในรูปแบบ HH:MM และต้องมากกว่าช่วงเริ่มต้น
        ]);

        // $stadium เป็นตัวแปรที่เก็บข้อมูลสนามใหม่ที่สร้างขึ้นมา
        $stadium = new Stadium();
        $stadium->stadium_name = $request->stadium_name; // ชื่อสนามที่รับจากฟอร์ม
        $stadium->stadium_price = $request->stadium_price; // ราคาสนามที่รับจากฟอร์ม
        $stadium->stadium_status = $request->stadium_status; // สถานะสนามที่รับจากฟอร์ม
        $stadium->save(); // บันทึกข้อมูลสนามใหม่ลงฐานข้อมูล
        
        // เก็บช่วงเวลาที่เลือก โดยเก็บเป็น "start_time-end_time"
        $startTimes = $request->input('start_time'); // $startTimes เก็บข้อมูล array ของช่วงเวลาเริ่มต้น
        $endTimes = $request->input('end_time'); // $endTimes เก็บข้อมูล array ของช่วงเวลาสิ้นสุด
        foreach ($startTimes as $index => $startTime) {
            // $timeSlot เป็นตัวแปรที่เก็บข้อมูลช่วงเวลาใหม่
            $timeSlot = new TimeSlot();
            $timeSlot->time_slot = $startTime . '-' . $endTimes[$index]; // เก็บเป็นสตริง "start-end"
            $timeSlot->stadium_id = $stadium->id; // เชื่อมโยงช่วงเวลาเข้ากับสนามที่สร้างใหม่
            $timeSlot->save(); // บันทึกข้อมูลช่วงเวลาลงฐานข้อมูล
        }
        
        // ส่งกลับไปยังหน้า stadium index พร้อมกับข้อความแสดงความสำเร็จ
        return redirect()->route('stadiums.index')->with('success', 'สนามและช่วงเวลาเพิ่มเรียบร้อยแล้ว');
    }

    // แสดงฟอร์มแก้ไขข้อมูลสนามตาม ID ที่กำหนด
    public function edit($id) {
        // $stadium เก็บข้อมูลสนามที่ดึงจากฐานข้อมูลตาม id
        $stadium = Stadium::findOrFail($id); 
        // $time_slots เก็บข้อมูลช่วงเวลาที่เชื่อมโยงกับสนามนี้ตาม id
        $time_slots = TimeSlot::where('stadium_id', $id)->get(); 

        // ตรวจสอบสิทธิ์ของผู้ใช้ (เฉพาะผู้ดูแลระบบเท่านั้นที่เข้าถึงได้)
        if (Auth::user()->is_admin != 1) {
            return redirect()->route('stadiums.show')->with('error', "You don't have admin access.");
        }

        return view('stadium.edit', compact('stadium', 'time_slots')); // ส่ง $stadium และ $time_slots ไปแสดงในฟอร์ม edit
    }

    // บันทึกข้อมูลสนามที่แก้ไขแล้ว
    public function update(Request $request, $id)
    {
        // ตรวจสอบข้อมูลที่ส่งมาใน $request
        $request->validate([
            'stadium_name' => 'required|string|max:255', // ชื่อสนาม
            'stadium_price' => 'required|numeric', // ราคาสนาม
            'stadium_status' => 'required|string', // สถานะของสนาม
            'start_time' => 'required|array', // ช่วงเวลาเริ่มต้น เป็น array
            'end_time' => 'required|array', // ช่วงเวลาสิ้นสุด เป็น array
            'start_time.*' => 'required|date_format:H:i', // ช่วงเวลาเริ่มต้นในรูปแบบ HH:MM
            'end_time.*' => 'required|date_format:H:i|after:start_time.*' // ช่วงเวลาสิ้นสุดในรูปแบบ HH:MM และต้องมากกว่าช่วงเริ่มต้น
        ]);

        // $stadium เป็นตัวแปรที่เก็บข้อมูลสนามที่ดึงจากฐานข้อมูลตาม id
        $stadium = Stadium::findOrFail($id);

        // อัปเดตข้อมูลสนาม
        $stadium->stadium_name = $request->stadium_name; // อัปเดตชื่อสนาม
        $stadium->stadium_price = $request->stadium_price; // อัปเดตราคาสนาม
        $stadium->stadium_status = $request->stadium_status; // อัปเดตสถานะสนาม
        $stadium->save(); // บันทึกการอัปเดตลงในฐานข้อมูล

        // ลบช่วงเวลาเก่าทั้งหมดที่เชื่อมโยงกับสนามนี้
        TimeSlot::where('stadium_id', $id)->delete();

        // เก็บช่วงเวลาที่อัปเดตใหม่
        $startTimes = $request->input('start_time'); // $startTimes เก็บ array ของช่วงเวลาเริ่มต้นใหม่
        $endTimes = $request->input('end_time'); // $endTimes เก็บ array ของช่วงเวลาสิ้นสุดใหม่
        foreach ($startTimes as $index => $startTime) {
            // $timeSlot เป็นตัวแปรที่เก็บข้อมูลช่วงเวลาใหม่
            $timeSlot = new TimeSlot();
            $timeSlot->time_slot = $startTime . '-' . $endTimes[$index]; // เก็บเป็นสตริง "start-end"
            $timeSlot->stadium_id = $stadium->id; // เชื่อมโยงช่วงเวลาเข้ากับสนามที่แก้ไข
            $timeSlot->save(); // บันทึกข้อมูลช่วงเวลาลงฐานข้อมูล
        }

        // ส่งกลับไปยังหน้า stadium index พร้อมกับข้อความแสดงความสำเร็จ
        return redirect()->route('stadiums.index')->with('success', 'สนามถูกอัปเดตเรียบร้อยแล้ว');
    }

    // ลบข้อมูลสนามและช่วงเวลาที่เกี่ยวข้องตาม ID
    public function destroy($id)
    {
        // $stadium เก็บข้อมูลสนามที่ดึงจากฐานข้อมูลตาม id
        $stadium = Stadium::findOrFail($id);

        // ลบช่วงเวลาที่เชื่อมโยงกับสนามนี้
        TimeSlot::where('stadium_id', $id)->delete();

        // ลบสนามจากฐานข้อมูล
        $stadium->delete();

        // ส่งกลับไปยังหน้าแสดงรายการสนามพร้อมข้อความแจ้งเตือนความสำเร็จ
        return redirect()->route('stadiums.index')->with('success', 'สนามถูกลบเรียบร้อยแล้ว');
    }
}
