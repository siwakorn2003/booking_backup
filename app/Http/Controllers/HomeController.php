<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Stadium;
use App\Models\item;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function adminHome()
    {
        // // นับจำนวนผู้ใช้ทั้งหมด
        // $totalUsers = User::count();

        // // นับจำนวนสนามทั้งหมด
        // $totalStadiums = Stadium::count();

        // // นับจำนวนสนามทั้งหมด
        // $totalItem = item::count();

        // // ส่งข้อมูลไปยัง view
        // return view('adminHome', compact('totalUsers', 'totalStadiums', 'totalItem'));

        return view('adminHome');
    
    }
}
