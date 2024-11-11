@extends('layouts.app')

@section('content')
    <main class="py-4">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-7">
                    <div class="card shadow-lg border-0">
                        <div style="background-color:#27299a;" class="card-header text-white text-center">
                            <h4>{{ __('การจองสนาม') }}</h4>
                        </div>
                        <div class="card-body p-3">
                           <!-- การเลือกวันที่ -->
                        <div class="mb-4">
                            <label for="booking-date" class="form-label">เลือกวันที่</label>
                            <div class="input-group">
                                <!-- ฟอร์มเลือกวันที่ -->
                                <input type="date" id="booking-date" class="form-control form-control-sm"
                                    min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                    max="{{ \Carbon\Carbon::now()->addDays(7)->format('Y-m-d') }}"
                                    value="{{ request()->get('date', \Carbon\Carbon::now()->format('Y-m-d')) }}"
                                    onchange="redirectToDate()">

                            
                            </div>
                        </div>

                            <!-- ปุ่มสถานะ -->
                            <div class="mb-4 text-start">
                                <button class="btn btn-md btn-success me-3">ว่าง</button>
                                <button class="btn btn-md btn-warning text-dark me-3">รอการตรวจสอบ</button>
                                <button class="btn btn-md btn-secondary">มีการจองแล้ว</button>
                            </div>


                            @foreach ($stadiums as $stadium)
                            <div class="mb-4">
                                <div class="card border-light">
                                    <div class="card-body border stadium-card">
                                        <h5 class="card-title">{{ $stadium->stadium_name }}</h5>
                                        <p class="card-text">ราคา: {{ number_format($stadium->stadium_price) }} บาท</p>
                                        <p class="card-text">สถานะ:
                                            <span class="badge @if ($stadium->stadium_status == 'พร้อมให้บริการ') bg-success @elseif ($stadium->stadium_status == 'ปิดปรับปรุง') bg-danger @else bg-warning @endif">
                                                {{ $stadium->stadium_status }}
                                            </span>
                                        </p>
                        
                                        <div class="d-flex flex-wrap">
                                            @foreach ($stadium->timeSlots as $timeSlot)
                                                @php
                                                    // กำหนดสถานะเริ่มต้นของปุ่ม
                                                    $statusClass = 'btn-outline-primary';
                                                    $statusText = 'ว่าง';
                        
                                                    // ตรวจสอบสถานะการจองในช่วงเวลานั้น
                                                    $status = $conflictingTimeSlots[$stadium->id][$timeSlot->id] ?? null;
                        
                                                    if ($stadium->stadium_status == 'ปิดปรับปรุง') {
                                                        // หากสนามอยู่ในสถานะ "ปิดปรับปรุง" ให้ปิดการใช้งานปุ่ม
                                                        $statusClass = 'btn-secondary disabled'; // สีเทา และ ปิดการใช้งานปุ่ม
                                                        $statusText = 'ปิดปรับปรุง';
                                                    } elseif ($status == 'รอการตรวจสอบ') {
                                                        // หากสถานะการจองเป็น "รอการตรวจสอบ"
                                                        $statusClass = 'btn-warning disabled'; // สีเหลือง และ ปิดการใช้งานปุ่ม
                                                        $statusText = 'รอการตรวจสอบ';
                                                    } elseif ($status == 'ชำระเงินแล้ว') {
                                                        // หากสถานะการจองเป็น "ชำระเงินแล้ว"
                                                        $statusClass = 'btn-secondary disabled'; // สีเทา และ ปิดการใช้งานปุ่ม
                                                        $statusText = 'ชำระเงินแล้ว';
                                                    }
                                                @endphp
                        
                                                <button 
                                                    class="btn m-1 time-slot-button {{ $statusClass }}" 
                                                    data-stadium="{{ $stadium->id }}"
                                                    data-time="{{ $timeSlot->time_slot }}"
                                                    onclick="selectTimeSlot(this, {{ $stadium->id }})"
                                                    {{ $statusClass === 'btn-warning disabled' || $statusClass === 'btn-secondary disabled' ? 'disabled' : '' }}
                                                >
                                                    {{ $timeSlot->time_slot }} - {{ $statusText }}
                                                </button>
                                            @endforeach
                                        </div>
                        
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        



                            <!-- ปุ่มจองตามสถานะการล็อกอิน -->
                            <div class="text-center">
                                @auth
                                    <button class="btn btn-primary" onclick="submitBooking()" id="booking-btn" disabled>
                                        {{ __('จองสนาม') }}
                                    </button>
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary"
                                        onclick="alert('โปรดเข้าสู่ระบบก่อนทำการจอง');">
                                        {{ __('จองสนาม') }}
                                    </a>
                                @endauth
                            </div>

                            <!-- พื้นที่แสดงผลลัพธ์การจอง -->
                            <div id="booking-result" class="text-center mt-4"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @push('scripts')
        <script>
            let selectedTimeSlots = {};

function selectTimeSlot(button, stadiumId) {
    const time = button.getAttribute('data-time');

    // ตรวจสอบว่าช่วงเวลาถูกเลือกไว้แล้วหรือไม่
    if (!selectedTimeSlots[stadiumId]) {
        selectedTimeSlots[stadiumId] = [];
    }

    const timeIndex = selectedTimeSlots[stadiumId].indexOf(time);
    if (timeIndex > -1) {
        selectedTimeSlots[stadiumId].splice(timeIndex, 1);
        button.classList.remove('btn-primary');
        button.classList.add('btn-outline-primary');
    } else {
        selectedTimeSlots[stadiumId].push(time);
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary');
    }
}


// เปิดใช้งานปุ่มจองสนามเมื่อโหลดหน้า
document.getElementById('booking-btn').disabled = false;

            

            // ฟังก์ชันสำหรับส่งการจอง
            function submitBooking() {
                const date = document.getElementById('booking-date').value;
                const selectedStadiums = Object.keys(selectedTimeSlots); // รับสนามที่เลือก

                // ตรวจสอบข้อมูลที่ป้อน
                if (!date) {
                    alert('กรุณาเลือกวันที่');
                    return;
                }

                if (selectedStadiums.length === 0) {
                    alert('กรุณาเลือกสนาม');
                    return;
                }

                const bookingData = {
                    date: date,
                    timeSlots: selectedTimeSlots, // ส่งช่วงเวลาที่เลือก
                    _token: '{{ csrf_token() }}' // CSRF token สำหรับความปลอดภัย
                };

                // ส่งข้อมูลการจองไปยังเซิร์ฟเวอร์
                fetch('{{ route('booking.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest' // แสดงว่าคำร้องนี้เป็น AJAX request
                        },
                        body: JSON.stringify(bookingData) // แปลงข้อมูลการจองเป็น JSON
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.login_required) {
                            window.location.href = '{{ route('login') }}';
                        } else if (data.success) {
                            // เปลี่ยนเส้นทางไปหน้ารายละเอียดการจอง
                            const bookingStadiumId = data.booking_stadium_id;
                            window.location.href = `{{ url('/bookingDetail') }}/${bookingStadiumId}`;

                            // ตรวจสอบการจองซ้ำ
                            if (data.conflictingTimeSlots && Object.keys(data.conflictingTimeSlots).length > 0) {
                                // ถ้ามีการจองซ้ำ
                                data.conflictingTimeSlots.forEach(timeSlot => {
                                    const button = document.querySelector(`button[data-time="${timeSlot}"]`);
                                    if (button) {
                                        button.classList.add('btn-secondary',
                                        'disabled'); // เพิ่มคลาสสีเทาและ disabled
                                        button.textContent = `${timeSlot} - จองซ้ำ`;
                                    }
                                });
                            }
                        } else {
                            document.getElementById('booking-result').innerHTML = '<div class="alert alert-danger">' + data
                                .message + '</div>';
                        }
                    })

                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('booking-result').innerHTML =
                            '<div class="alert alert-danger">เกิดข้อผิดพลาดในการส่งคำขอการจอง</div>';
                    });
            }

          

             // ฟังก์ชันที่ใช้เมื่อเลือกวันที่
    function redirectToDate() {
        var selectedDate = document.getElementById('booking-date').value;
        if (selectedDate) {
            // รีเฟรชหน้าด้วยการส่งพารามิเตอร์วันที่ไปใน URL
            window.location.href = window.location.pathname + "?date=" + selectedDate;
        }
    }
        </script>
    @endpush
@endsection

