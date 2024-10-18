<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stadium;
use App\Models\item;


class AdminController extends Controller
{
    public function index()
    {
        // นับจำนวนผู้ใช้ทั้งหมด
        $totalUsers = User::count();

        // นับจำนวนสนามทั้งหมด
        $totalStadiums = Stadium::count();

        // นับจำนวนสนามทั้งหมด
        $totalItem = item::count();

        // ส่งข้อมูลไปยัง view
        return view('adminHome', compact('totalUsers', 'totalStadiums', 'totalItem'));
    }
}
