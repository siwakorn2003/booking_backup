@extends('layouts.app')

@section('content')
<main class="py-4">
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>{{ __('รายละเอียดการจอง') }}</h4>
                    </div>
                    <div class="card-body p-3">
                        <p>วันที่: {{ $bookingDetails[0]['date'] }}</p>
                        <p>ชื่อผู้ใช้: {{ $user->fname }}</p>
                        <p>เบอร์โทรศัพท์: {{ $user->phone }}</p>
                        <p>สนาม: {{ $stadium->stadium_name }}</p> <!-- ใช้ข้อมูลจาก $stadium -->
                        
                        <p>เวลา: 
                            @foreach ($bookingDetails as $detail)
                                {{ $detail['time_slot'] }}{{ !$loop->last ? ',' : '' }}
                            @endforeach
                        </p>
                        
                        <p>ราคา: {{ number_format($stadium->stadium_price) }} บาทต่อชั่วโมง</p>
                        <p>ชั่วโมงรวม: {{ $totalHours }} ชั่วโมง</p>
                        <p>ราคาทั้งหมด: {{ number_format($totalHours * $stadium->stadium_price) }} บาท</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
