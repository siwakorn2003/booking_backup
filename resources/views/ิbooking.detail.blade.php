<!-- resources/views/booking/detail.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>รายละเอียดการจอง</h1>
    <p>สนาม: {{ $booking->stadium->stadium_name }}</p>
    <p>วันที่จอง: {{ $booking->date }}</p>
    <p>เวลา: {{ $booking->time_slot }}</p>
    <p>ราคา: {{ $booking->price }}</p>
</div>
@endsection
