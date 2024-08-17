<?php

namespace App\Http\Controllers;

use App\Models\Stadium;
use Illuminate\Http\Request;

class StadiumController extends Controller
{
    public function index() {
        $stadiums = Stadium::all();
        return view('stadium.index', compact('stadiums'));
    }
        

    public function create() {
        return view('stadium.create');
    }

    public function store(Request $request) {
        $request->validate([
            'stadium_name' => 'required',
            'stadium_price' => 'required|numeric',
            'stadium_picture' => 'required|image',
            'stadium_status' => 'required|in:พร้อมให้บริการ,ปิดปรับปรุง',
        ]);
    
        $path = $request->file('stadium_picture')->store('stadium_pictures', 'public');
        
        Stadium::create([
            'stadium_name' => $request->stadium_name,
            'stadium_price' => $request->stadium_price,
            'stadium_picture' => $path,
            'stadium_status' => $request->stadium_status,
        ]);
    
        return redirect()->route('stadiums.index')->with('success', 'สนามถูกเพิ่มเรียบร้อยแล้ว');
    }

    public function edit(Stadium $stadium) {
        return view('stadium.edit', compact('stadium'));
    }

    public function update(Request $request, Stadium $stadium) {
        $request->validate([
            'stadium_name' => 'required',
            'stadium_price' => 'required|numeric',
            'stadium_picture' => 'image',
            'stadium_status' => 'required|in:พร้อมให้บริการ,ปิดปรับปรุง',
        ]);
    
        if ($request->hasFile('stadium_picture')) {
            $path = $request->file('stadium_picture')->store('stadium_pictures', 'public');
            $stadium->stadium_picture = $path;
        }
    
        $stadium->stadium_name = $request->stadium_name;
        $stadium->stadium_price = $request->stadium_price;
        $stadium->stadium_status = $request->stadium_status;
    
        $stadium->save();
    
        return redirect()->route('stadiums.index')->with('success', 'ข้อมูลสนามถูกแก้ไขเรียบร้อยแล้ว');
    }

    public function destroy(Stadium $stadium) {
        $stadium->delete();
        return redirect()->route('stadiums.index')->with('success', 'สนามถูกลบเรียบร้อยแล้ว');
    }
    
}
