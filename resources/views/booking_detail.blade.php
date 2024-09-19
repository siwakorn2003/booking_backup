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
                                    <th>รหัสการจอง</th>
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
                                    <td>{{ $booking->booking_id }}</td>
                                    <td>{{ Auth::user()->name }}</td>
                                    <td>{{ Auth::user()->phone_number }}</td>
                                    <td>{{ $booking->stadium_name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') }}</td>
                                    <td>{{ $booking->time }}</td>
                                    <td>{{ number_format($booking->stadium_price) }} บาท</td>
                                    <td>{{ $booking->time_slot }}</td> <!-- คำนวณเวลาจาก time_slot -->
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
