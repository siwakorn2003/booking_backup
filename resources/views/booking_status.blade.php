@extends('layouts.app')

@section('content')
<main class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>{{ __('สถานะการจอง') }}</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อจริง</th>
                                    <th>เบอร์โทรศัพท์</th>
                                    <th>รายการ</th>
                                    <th>วันที่</th>
                                    <th>เวลา</th>
                                    <th>ราคา</th>
                                    <th>จำนวนชั่วโมง</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $booking->id }}</td>
                                    <td>{{ $booking->user->name }}</td>
                                    <td>{{ $booking->user->phone_number }}</td>
                                    <td>{{ $booking->stadium->stadium_name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') }}</td>
                                    <td>{{ $booking->timeSlot->time_slot }}</td>
                                    <td>{{ number_format($booking->stadium->stadium_price) }} บาท</td>
                                    <td>1 ชั่วโมง</td> <!-- คำนวณจำนวนชั่วโมงจาก time_slot ได้ตามที่ต้องการ -->
                                </tr>
                            </tbody>
                        </table>
                        <div class="text-center">
                            <button class="btn btn-success">ยืนยันการจอง</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
