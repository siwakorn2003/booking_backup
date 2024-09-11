@extends('layouts.app')

@section('content')
<main class="py-4">
    <div class="container">
        <h3 class="mb-4">{{ __('สถานะการจอง') }}</h3>
        <div class="card shadow-sm p-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>รหัสการจอง</th>
                        <th>ชื่อผู้ใช้</th>
                        <th>เบอร์โทรศัพท์</th>
                        <th>ชื่อสนาม</th>
                        <th>วันที่</th>
                        <th>เวลา</th>
                        <th>ราคา</th>
                        <th>ชั่วโมง</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>12345</td> <!-- สมมุติรหัสการจอง -->
                        <td>{{ Auth::user()->fname }}</td> <!-- ชื่อผู้ใช้ที่ล็อกอิน -->
                        <td>{{ Auth::user()->phone }}</td> <!-- เบอร์โทรของผู้ใช้ -->
                        <td>{{ $stadiumName }}</td>
                        <td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                        <td>{{ $timeSlots }}</td>
                        <td>{{ number_format($stadiumPrice) }} บาท</td>
                        <td>{{ count(explode(',', $timeSlots)) }} ชั่วโมง</td>
                    </tr>
                </tbody>
            </table>

            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-secondary" onclick="goBack()">{{ __('ย้อนกลับ') }}</button>
                <div>
                    <span>รวมยอด {{ number_format($stadiumPrice * count(explode(',', $timeSlots))) }} บาท</span>
                    <button class="btn btn-success ms-3" onclick="confirmBooking()">{{ __('ยืนยันการจอง') }}</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function goBack() {
        window.history.back();
    }

    function confirmBooking() {
        alert('ยืนยันการจอง');
        // เพิ่มฟังก์ชันสำหรับการยืนยันการจองที่นี่
    }
</script>
@endsection

