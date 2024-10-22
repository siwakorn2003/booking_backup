@extends('layouts.app')

@section('content')
    <main class="py-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>รายละเอียดการจอง</h1>
                @if (isset($groupedBookingDetails) && $groupedBookingDetails->isNotEmpty())
                    <span class="badge bg-info">{{ $groupedBookingDetails->first()['booking_status'] }}</span>
                @endif
            </div>

            @if (isset($groupedBookingDetails) && $groupedBookingDetails->isNotEmpty())
                @php
                    $firstGroup = $groupedBookingDetails->first();
                @endphp
                <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-4">
                    @if($booking_stadium_id)
                        <p class="mb-0 fw-bold">รหัสการจอง: <span class="text-success">{{ $booking_stadium_id }}</span></p>
                    @endif
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <!-- ถ้ามีข้อความแจ้งเตือน ไม่มีข้อมูลการจอง -->
            @if (isset($message))
                <div class="alert alert-info">
                    {{ $message }}
                </div>
            @elseif ($groupedBookingDetails->isNotEmpty())
                <!-- ถ้ามีข้อมูลการจอง -->
                <table class="table table-bordered table-striped mt-4">
                    <thead class="table-light">
                        <tr>
                            <th>สนาม</th>
                            <th>วันที่จอง</th>
                            <th>เวลา</th>
                            <th>ราคา</th>
                            <th>ชั่วโมง</th>
                            <th>ลบ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupedBookingDetails as $group)
                            <tr>
                                <td>{{ $group['stadium_name'] }}</td>
                                <td>{{ $group['booking_date'] }}</td>
                                <td>{{ $group['time_slots'] }}</td>
                                <td>{{ number_format($group['total_price']) }} บาท</td>
                                <td>{{ $group['total_hours'] }}</td>
                                <td>
                                    {{-- <button class="btn btn-outline-danger delete-booking" data-id="{{ $group['id'] }}">ลบ</button> --}}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>ไม่พบข้อมูลการจอง</p>
            @endif

            <!-- แสดงรายละเอียดการยืมด้านล่าง -->
            @if ($borrowingDetails->isNotEmpty())
                <h2 class="mt-5">รายละเอียดการยืมอุปกรณ์</h2>
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>รหัสการยืม</th>
                            <th>ชื่อจริง</th>
                            <th>ชื่ออุปกรณ์</th>
                            <th>สนามที่ใช้</th>
                            <th>วันที่ยืม</th>
                            <th>เวลา</th>
                            <th>จำนวน</th>
                            <th>ราคา</th>
                            <th>สถานะ</th>
                            <th>ลบ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($borrowingDetails as $borrow)
                            @foreach ($borrow->details as $detail)
                                <!-- วนลูปเพื่อแสดงรายละเอียด -->
                                <tr id="borrow-row-{{ $borrow->id }}">
                                    <td>{{ $borrow->id }}</td>
                                    <td>{{ $borrow->user->fname }}</td>
                                    <td>{{ $detail->item->item_name }}</td>
                                    <td>{{ $detail->stadium->stadium_name }}</td>
                                    <td>{{ $borrow->borrow_date }}</td>
                                    <td>
                                        @if ($detail->timeSlot)
                                            <!-- ตรวจสอบว่ามีข้อมูลเวลา -->
                                            {{ $detail->timeSlot->time_slot }} <!-- แสดงช่วงเวลาจาก time slot -->
                                        @else
                                            ไม่มีข้อมูลเวลา
                                        @endif
                                    </td>
                                    <td>{{ $detail->borrow_quantity }}</td>
                                    <td>{{ number_format($detail->borrow_total_price) }} บาท</td>
                                    <td>{{ $borrow->borrow_status }}</td>
                                    <td>
                                        <button class="btn btn-outline-danger delete-borrow"
                                            data-id="{{ $borrow->id }}">ลบ</button>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>ยังไม่มีการยืมอุปกรณ์</p>
            @endif

            <!-- เงื่อนไขสำหรับปุ่มยืมอุปกรณ์ -->
            @if (!empty($bookingDetails) && $bookingDetails->isNotEmpty())
                <h3 class="mt-3">สามารถยืมอุปกรณ์ได้</h3>
            @else
                <p>คุณต้องจองสนามก่อนนะ ถึงจะสามารถยืมอุปกรณ์ได้</p>
            @endif

            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-outline-secondary" onclick="window.location='{{ route('booking') }}'">ย้อนกลับ</button>
                <div>
                    <div class="text-end">
                        <!-- ปุ่มยืนยันการจอง -->
                        @if($booking_stadium_id)
                            <form action="{{ route('confirmBooking', ['booking_stadium_id' => $booking_stadium_id]) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">ยืนยันการจอง</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript ส่วนจัดการลบข้อมูลแบบ AJAX -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- โหลด jQuery -->
<script>
    $(document).ready(function() {
        // เมื่อคลิกปุ่มลบการจอง (จองสนาม)
        $('.delete-booking').on('click', function(e) {
            e.preventDefault(); // ป้องกันการทำงานปกติของลิงก์หรือปุ่ม
            var bookingId = $(this).data('id'); // ดึงค่า bookingId จาก data-id ในปุ่มที่กด
            var row = $('#booking-row-' + bookingId); // หาแถว (row) ของข้อมูลการจองที่ต้องการลบ

            // ยืนยันกับผู้ใช้ก่อนที่จะลบข้อมูล
            if (confirm('คุณแน่ใจที่จะลบรายการนี้?')) {
                // ส่งคำขอ AJAX เพื่อทำการลบข้อมูลการจอง
                $.ajax({
                    url: '/booking/' + bookingId, // URL ของคำขอลบข้อมูล (ระบุ bookingId ที่ต้องการลบ)
                    type: 'DELETE', // ประเภทของคำขอ HTTP คือ DELETE
                    data: {
                        _token: '{{ csrf_token() }}' // ส่งค่า CSRF token เพื่อยืนยันความปลอดภัย
                    },
                    success: function(response) {
                        // หากลบข้อมูลสำเร็จ, ลบแถว (row) ของข้อมูลในตารางออก
                        row.remove();
                        alert('ลบรายการจองสำเร็จ'); // แสดงข้อความแจ้งเตือน
                    },
                    error: function(xhr) {
                        // หากเกิดข้อผิดพลาดในการลบข้อมูล
                        alert('เกิดข้อผิดพลาดในการลบข้อมูล');
                    }
                });
            }
        });

        // เมื่อคลิกปุ่มลบการยืม (ยืมอุปกรณ์)
        $('.delete-borrow').on('click', function(e) {
            e.preventDefault(); // ป้องกันการทำงานปกติของลิงก์หรือปุ่ม
            var borrowId = $(this).data('id'); // ดึงค่า borrowId จาก data-id ในปุ่มที่กด
            var row = $('#borrow-row-' + borrowId); // หาแถว (row) ของข้อมูลการยืมที่ต้องการลบ

            // ยืนยันกับผู้ใช้ก่อนที่จะลบข้อมูล
            if (confirm('คุณแน่ใจที่จะลบรายการนี้?')) {
                // ส่งคำขอ AJAX เพื่อทำการลบข้อมูลการยืม
                $.ajax({
                    url: '/lending/borrow/' + borrowId, // URL ของคำขอลบข้อมูลการยืม (ระบุ borrowId ที่ต้องการลบ)
                    type: 'DELETE', // ประเภทของคำขอ HTTP คือ DELETE
                    data: {
                        _token: '{{ csrf_token() }}' // ส่งค่า CSRF token เพื่อยืนยันความปลอดภัย
                    },
                    success: function(response) {
                        // หากลบข้อมูลสำเร็จ, ลบแถว (row) ของข้อมูลในตารางออก
                        row.remove();
                        alert('ลบรายการยืมสำเร็จ'); // แสดงข้อความแจ้งเตือน
                    },
                    error: function(xhr) {
                        // หากเกิดข้อผิดพลาดในการลบข้อมูล
                        alert('เกิดข้อผิดพลาดในการลบข้อมูล');
                    }
                });
            }
        });
    });
</script>

@endsection