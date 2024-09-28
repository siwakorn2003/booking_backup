@extends('layouts.app')

@section('content')
<main class="py-4">
    <div class="container">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">รายละเอียดการจอง</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>รหัสการจอง</th>
                            <th>ชื่อจริง</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>รายการ</th>
                            <th>วันที่</th>
                            <th>เวลา</th>
                            <th>ราคา</th>
                            <th>ชั่วโมง</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booking_code }}</td>
                            <td>{{ $booking->user->name }}</td>
                            <td>{{ $booking->user->phone }}</td>
                            <td>{{ $booking->stadium->stadium_name }} ({{ $booking->people_count }} คน)</td>
                            <td>{{ \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') }}</td>
                            <td>{{ $booking->start_time }} - {{ $booking->end_time }}</td>
                            <td>{{ number_format($booking->price_per_hour) }} บาท</td>
                            <td>{{ $booking->total_hours }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="row">
                    <div class="col-md-6 text-start">
                        <a href="{{ route('booking.previous') }}" class="btn btn-secondary">ย้อนกลับ</a>
                    </div>
                    <div class="col-md-6 text-end">
                        <h5>รวมยอด: {{ number_format($total_price) }} บาท</h5>
                        <button class="btn btn-success" onclick="confirmBooking()">ยืนยันการจอง</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function confirmBooking() {
        if (confirm('คุณต้องการยืนยันการจองใช่หรือไม่?')) {
            document.getElementById('confirm-booking-form').submit();
        }
    }
</script>

<form id="confirm-booking-form" method="POST" action="{{ route('booking.confirm') }}">
    @csrf
    <input type="hidden" name="booking_ids" value="{{ json_encode($booking_ids) }}">
</form>
@endsection
