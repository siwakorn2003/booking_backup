@extends('layouts.app')

@section('content')
<div class="container">
    @if(session('success'))
        <div id="success-message" class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('You are logged in!') }}

                    You are Normal User.

                    <a href="calendar">ปฏิทิน</a>
                    <a href="{{ route('booking.from') }}" class="btn btn-primary">จองสนามฟุตบอล</a>

                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ตรวจสอบว่ามีข้อความสำเร็จหรือไม่
        var successMessage = document.getElementById('success-message');
        if (successMessage) {
            // ตั้งเวลาให้ข้อความหายไปหลังจาก 5 วินาที
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 5000); // 5000 มิลลิวินาที = 5 วินาที
        }
    });
</script>
@endsection

