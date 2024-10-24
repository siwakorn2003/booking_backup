<!-- resources/views/paymentNotification.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container col-3">
    <h2 class="text-center mb-4">แจ้งการชำระเงิน</h2>
    <form action="" method="POST" enctype="multipart/form-data" class="shadow-lg p-4 rounded">
        @csrf

        <div class="form-group mb-3">
            <label for="booking_code">รหัสการจอง*</label>
            <input type="text" id="booking_code" name="booking_code" class="form-control" required>
        </div>

        <div class="form-group mb-3">
            <label for="payer_name">ชื่อผู้โอน*</label>
            <input type="text" id="payer_name" name="payer_name" class="form-control" required>
        </div>

        <div class="form-group mb-3">
            <label for="phone_number">เบอร์โทรศัพท์*</label>
            <input type="tel" id="phone_number" name="phone_number" class="form-control" required>
        </div>

        <div class="form-group mb-3">
            <label for="transfer_datetime">วันที่และเวลาโอน*</label>
            <input type="datetime-local" id="transfer_datetime" name="transfer_datetime" class="form-control" required>
        </div>

        <div class="form-group mb-3">
            <label for="transfer_amount">จำนวนเงินที่โอน*</label>
            <input type="number" id="transfer_amount" name="transfer_amount" class="form-control" required>
        </div>

        <div class="form-group mb-3">
            <label for="transfer_slip">โอนแล้วอัปโหลดสลิปได้ที่</label>
            <input type="file" id="transfer_slip" name="transfer_slip" class="form-control" accept="image/*" required>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary">ยืนยันการชำระเงิน</button>
            <a href="{{ route('home') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection
