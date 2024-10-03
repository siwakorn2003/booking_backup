@extends('layouts.app')

@section('content')
<main class="py-4">
    <div class="container">
        <h1>รายละเอียดการจอง</h1>

        @if ($bookingDetails->isNotEmpty())
        @foreach ($bookingDetails as $detail)
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">สนาม: {{ $detail->stadium->stadium_name }}</h5>
                    <p class="card-text">วันที่จอง: {{ $detail->booking_date }}</p>
                    <p class="card-text">เวลาที่จอง: {{ $detail->timeSlot->time_slot }}</p>
                    <p class="card-text">ราคาทั้งหมด: {{ number_format($detail->booking_total_price) }} บาท</p>
                </div>
            </div>
        @endforeach
    @else
        <p>ไม่พบข้อมูลการจอง</p>
    @endif
    
    </div>
</main>
@endsection
