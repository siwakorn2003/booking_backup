@extends('layouts.app')
@stack('styles')

@section('content')
<main class="py-4">
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>{{ __('การจองสนาม') }}</h4>
                    </div>
                    <div class="card-body p-3">
                        <!-- Date Picker -->
                    <div class="mb-4">
                        <label for="booking-date" class="form-label">เลือกวันที่</label>
                        <input type="date" id="booking-date" class="form-control" value="{{ $date }}" onchange="updateBookings()" min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" max="{{ \Carbon\Carbon::now()->addDays(7)->format('Y-m-d') }}">
                    </div>

                        <!-- Status Indicators -->
                        <div class="mb-4 text-start">
                            <button class="btn btn-md btn-success me-3">ว่าง</button>
                            <button class="btn btn-md btn-warning text-dark me-3">รอการตรวจสอบ</button>
                            <button class="btn btn-md btn-secondary">มีการจองแล้ว</button>
                        </div>

                        <!-- Fields -->
                        @foreach ($stadiums as $stadium)
                        <div class="mb-4">
                            <div class="card border-light">
                                <div class="card-body border stadium-card">
                                    <h5 class="card-title">{{ $stadium->stadium_name }}</h5>
                                    <p class="card-text">ราคา: {{ number_format($stadium->stadium_price) }} บาท</p>
                                    <p class="card-text">สถานะ: 
                                        <span class="badge 
                                            @if ($stadium->stadium_status == 'พร้อมให้บริการ') 
                                                bg-success
                                            @elseif ($stadium->stadium_status == 'ปิดปรับปรุง') 
                                                bg-danger
                                            @else 
                                                bg-secondary
                                            @endif">
                                            {{ $stadium->stadium_status }}
                                        </span>
                                    </p>
                                    <div class="d-flex flex-wrap">
                                        @foreach (['11:00-12:00', '12:00-13:00', '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00', '17:00-18:00'] as $slot)
                                        @php
                                            $status = 'btn-success'; // Default to available
                                            $startTime = \Carbon\Carbon::createFromFormat('H:i', explode('-', $slot)[0]);
                                            $booking = $bookings->first(function ($booking) use ($stadium, $startTime) {
                                                return $booking->stadium_id == $stadium->id && $booking->start_time->eq($startTime);
                                            });
                                    
                                            if ($stadium->stadium_status == 'ปิดปรับปรุง') {
                                                $status = 'btn-secondary'; // Disabled
                                                $disabled = 'disabled'; // Add the disabled attribute
                                            } elseif ($booking) {
                                                if ($booking->booking_status == 1) {
                                                    $status = 'btn-secondary'; // Booked
                                                } elseif ($booking->booking_status == 0) {
                                                    $status = 'btn-warning'; // Pending
                                                }
                                            }
                                        @endphp
                                        <button class="btn {{ $status }} text-white me-2 mb-2 time-slot-btn" 
                                                data-slot="{{ $slot }}" 
                                                data-stadium-id="{{ $stadium->id }}" 
                                                onclick="selectTimeSlot(this)"
                                                {{ $disabled ?? '' }}> <!-- Apply the disabled attribute -->
                                            {{ $slot }}
                                        </button>
                                    @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach

                        <!-- Booking Button -->
                        <div class="text-center">
                            <button class="btn btn-primary" onclick="submitBooking()">จองสนาม</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@push('scripts')
<script>
    let selectedSlots = {};

    function selectTimeSlot(button) {
        if (button.disabled) {
            return; // Do nothing if the button is disabled
        }

        const stadiumId = button.getAttribute('data-stadium-id');
        const slot = button.getAttribute('data-slot');

        if (!selectedSlots[stadiumId]) {
            selectedSlots[stadiumId] = [];
        }

        if (selectedSlots[stadiumId].includes(slot)) {
            selectedSlots[stadiumId] = selectedSlots[stadiumId].filter(s => s !== slot);
            button.classList.remove('btn-info');
            button.classList.add('btn-success');
        } else {
            selectedSlots[stadiumId].push(slot);
            button.classList.remove('btn-success');
            button.classList.add('btn-info');
        }
    }

    function submitBooking() {
        const date = document.getElementById('booking-date').value;
        const bookingData = {
            date: date,
            slots: selectedSlots
        };

        // Send bookingData to server (e.g., via AJAX or form submission)
        console.log('Booking Data:', bookingData);
        // Your AJAX call or form submission logic here
    }

    function updateBookings() {
        const bookingDateInput = document.getElementById('booking-date');
        const selectedDate = new Date(bookingDateInput.value);
        const today = new Date();
        const maxDate = new Date();
        maxDate.setDate(today.getDate() + 7);

        if (selectedDate < today || selectedDate > maxDate) {
            alert('กรุณาเลือกวันที่ภายใน 7 วันจากวันนี้');
            bookingDateInput.value = ''; // Clear the input
            return;
        }

        // Proceed with updating bookings
        window.location.href = `{{ route('booking') }}?date=${bookingDateInput.value}`;
    }
</script>
@endpush


@push('styles')
<style>
    .stadium-card {
        border: 2px solid #0050a7;
        padding: 15px;
        margin-bottom: 15px;
    }

    .time-slot-btn {
        border: 2px solid #ccc;
        padding: 5px 10px;
    }

    .time-slot-btn.btn-info {
        background-color: #17a2b8;
        border-color: #17a2b8;
    }
</style>
@endpush
@endsection