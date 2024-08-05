@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h4>{{ __('Dashboard') }}</h4>
                </div>
                <div class="card-body p-4">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="text-center mb-4">
                        
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card text-white bg-info shadow-sm h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <h5 class="card-title">การจัดการสมาชิก</h5>
                                    <p class="card-text">เพิ่ม ลบ และแก้ไขข้อมูลสมาชิก</p>
                                    <a href="#" class="btn btn-light align-self-end">จัดการสมาชิก</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card text-white bg-success shadow-sm h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <h5 class="card-title">การจองสนาม</h5>
                                    <p class="card-text">ดูและจัดการการจองสนามทั้งหมด</p>
                                    <a href="#" class="btn btn-light align-self-end">จัดการการจอง</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card text-white bg-warning shadow-sm h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <h5 class="card-title">สถานะการชำระเงิน</h5>
                                    <p class="card-text">ตรวจสอบและอัพเดตสถานะการชำระเงิน</p>
                                    <a href="#" class="btn btn-light align-self-end">จัดการสถานะ</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card text-white bg-danger shadow-sm h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <h5 class="card-title">การยืมอุปกรณ์</h5>
                                    <p class="card-text">ตรวจสอบและจัดการการยืมอุปกรณ์</p>
                                    <a href="#" class="btn btn-light align-self-end">จัดการการยืม</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="calendar" class="btn btn-primary mt-3">ปฏิทิน</a>
                    </div>

                    <hr class="my-4">

                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-center">การจองสนามวันนี้</h5>
                                    <p class="card-text text-center display-4">5 สนาม</p>
                                    {{-- Replace the hardcoded number with dynamic data --}}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-center">การยืมอุปกรณ์วันนี้</h5>
                                    <ul class="list-group list-group-flush">
                                        {{-- Replace the hardcoded items with dynamic data --}}
                                        <li class="list-group-item">ฟุตบอล - 10 ลูก</li>
                                        <li class="list-group-item">เสื้อเอี้ยม - 15 ตัว</li>
                                        <li class="list-group-item">รองเท้า - 20 คู่</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection