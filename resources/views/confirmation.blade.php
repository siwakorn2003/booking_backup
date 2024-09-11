@extends('layouts.app')
@stack('styles')

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
                        <th>รายการ</th>
                        <th>วันที่</th>
                        <th>เวลา</th>
                        <th>ราคา</th>
                        <th>ชั่วโมง</th>
                        {{-- <th>ลบ</th> --}}
                    </tr>
                </thead>
                <tbody>
                    <!-- สมมุติว่า $bookings ถูกส่งมาจากคอนโทรลเลอร์ -->
                    @foreach($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booking_id }}</td>
                            <td>{{ $booking->user->fname }}</td>
                            <td>{{ $booking->user->phone }}</td>
                            <td>{{ $booking->stadium->stadium_name }} ({{ $booking->stadium->capacity }} คน)</td>
                            <td>{{ \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') }}</td>
                            <td>{{ $booking->time_slot }}</td>
                            <td>{{ number_format($booking->stadium->price) }} บาท</td>
                            <td>{{ $booking->booking_total_hour }}</td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="removeBooking({{ $booking->id }})">ลบ</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-secondary" onclick="goBack()">{{ __('ย้อนกลับ') }}</button>
                <div>
                    <span>รวมยอด {{ number_format($totalPrice) }} บาท</span>
                    <button class="btn btn-success ms-3" onclick="confirmBooking()">{{ __('ยืนยันการจอง') }}</button>
                </div>
            </div>
        </div>
    </div>
</main>

@push('scripts')
<script>
    function removeBooking(bookingId) {
        // ฟังก์ชันสำหรับลบการจอง
        alert('ลบการจองหมายเลข: ' + bookingId);
        // เพิ่ม AJAX หรือการส่งฟอร์มสำหรับลบการจอง
    }

    function goBack() {
        window.history.back();
    }

    function confirmBooking() {
        // ฟังก์ชันสำหรับยืนยันการจอง
        alert('ยืนยันการจอง');
        // เพิ่ม AJAX หรือการส่งฟอร์มสำหรับยืนยันการจอง
    }
</script>
@endpush

@push('styles')
<style>
    .table th, .table td {
        text-align: center;
        vertical-align: middle;
    }
</style>
@endpush
@endsection
