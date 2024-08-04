<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Middleware\IsAdmin;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return view('home');
    
});



Auth::routes();
Route::get('/home', [HomeController::class, 'index'])->name('home');
// เส้นทางสำหรับหน้าแรกของผู้ดูแลระบบ
Route::get('/admin/home', [HomeController::class, 'adminHome'])
    ->name('admin.home')
    ->middleware(IsAdmin::class);

  
// เส้นทางของปฏิทิน
// Route::get('/calendar', [CalendarController::class, 'index']);
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
Route::post('/calendar', [CalendarController::class, 'index']);
//เส้นทางการแก้ไขโปรไฟล์
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
Route::get('/home', [HomeController::class, 'index'])->name('home');

