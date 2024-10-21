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
                    @if ($booking_stadium_id)
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
                                <button class="btn btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#lendingModal"
                                    data-stadium-name="{{ $group['stadium_name'] }}"
                                    data-booking-date="{{ $group['booking_date'] }}"
                                    data-time-slots="{{ $group['time_slots'] }}">
                                    ยืมอุปกรณ์
                                </button>
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
                <!-- ปุ่มไปหน้ายืมอุปกรณ์ -->
                <div class="text-end">

                </div>
            @else
                <p>คุณต้องจองสนามก่อนนะ ถึงจะสามารถยืมอุปกรณ์ได้</p>
                <div class="text-end">
                    <a href="{{ route('booking') }}" class="btn btn-warning">ไปจองสนาม</a>
                </div>
            @endif


            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-outline-secondary"
                    onclick="window.location='{{ route('booking') }}'">ย้อนกลับ</button>
                <div>
                    <div class="text-end">
                        <!-- ปุ่มยืนยันการจอง -->
                        @if ($booking_stadium_id)
                            <form action="{{ route('confirmBooking', ['booking_stadium_id' => $booking_stadium_id]) }}"
                                method="POST" class="d-inline">
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // เมื่อคลิกปุ่มลบการจอง
            $('.delete-booking').on('click', function(e) {
                e.preventDefault();
                var bookingId = $(this).data('id');
                var row = $('#booking-row-' + bookingId);

                if (confirm('คุณแน่ใจที่จะลบรายการนี้?')) {
                    $.ajax({
                        url: '/booking/' + bookingId,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            row.remove();
                            alert('ลบรายการจองสำเร็จ');
                        },
                        error: function(xhr) {
                            alert('เกิดข้อผิดพลาดในการลบข้อมูล');
                        }
                    });
                }
            });

            // เมื่อคลิกปุ่มลบการยืม
            $('.delete-borrow').on('click', function(e) {
                e.preventDefault();
                var borrowId = $(this).data('id');
                var row = $('#borrow-row-' + borrowId);

                if (confirm('คุณแน่ใจที่จะลบรายการนี้?')) {
                    $.ajax({
                        url: '/lending/borrow/' + borrowId, // เส้นทางสำหรับลบการยืม
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            row.remove();
                            alert('ลบรายการยืมสำเร็จ');
                        },
                        error: function(xhr) {
                            alert('เกิดข้อผิดพลาดในการลบข้อมูล');
                        }
                    });
                }
            });
        });

        // เมื่อ modal ถูกเปิด
$('#lendingModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget); // ปุ่มที่ถูกคลิกเพื่อเปิด modal
    var stadiumName = button.data('stadium-name'); // ดึงข้อมูลชื่อสนาม
    var bookingDate = button.data('booking-date'); // ดึงข้อมูลวันที่จอง
    var timeSlots = button.data('time-slots'); // ดึงข้อมูลช่วงเวลา

    // ค้นหา modal และแสดงข้อมูล
    var modal = $(this);
    modal.find('.stadium-name').text(stadiumName); // แสดงชื่อสนาม
    modal.find('#booking_date_display').text(bookingDate); // แสดงวันที่จอง
    modal.find('#time_slots_display').text(timeSlots); // แสดงช่วงเวลา

    // ตั้งค่า input hidden ในฟอร์ม
    modal.find('#stadium_id').val(button.data('stadium-id')); // ตั้งค่า stadium_id
    modal.find('#booking_date').val(bookingDate); // ตั้งค่า booking_date
    modal.find('#time_slots').val(timeSlots); // ตั้งค่า time_slots
});



    </script>

<!-- Modal สำหรับยืมอุปกรณ์ -->
<div class="modal fade" id="lendingModal" tabindex="-1" aria-labelledby="lendingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lendingModalLabel">ยืมอุปกรณ์</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('lending.borrowItem') }}" method="POST" id="lendingForm">
                    @csrf
                    <!-- ส่งข้อมูลที่จำเป็น เช่น stadium_id, booking_date, และ time_slots -->
                    <input type="hidden" name="stadium_id" id="stadium_id" value="{{ $booking->stadium_id ?? '' }}">
                    <input type="hidden" name="booking_date" id="booking_date" value="{{ $booking->booking_date ?? '' }}">
                    <input type="hidden" name="time_slots" id="time_slots" value="{{ $booking->time_slots ?? '' }}">

                    <!-- แสดงรายละเอียดการจอง -->
                    <div class="border p-3 mb-3">
                        <h6>รายละเอียดการจอง</h6>
                        <p><strong>สนามที่ยืมอุปกรณ์:</strong> <span class="stadium-name">{{ $group['stadium_name'] ?? 'ไม่มีข้อมูล' }}</span></p>
                        <p><strong>วันที่จองและยืม:</strong> <span id="booking_date_display">{{ $group['booking_date'] ?? 'ไม่มีข้อมูล' }}</span></p>
                        <p><strong>ช่วงเวลาที่จองและยืม:</strong> <span id="time_slots_display">{{ $group['time_slots'] ?? 'ไม่มีข้อมูล' }}</span></p>
                    </div>
                    
                    

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>รหัสอุปกรณ์</th>
                                    <th>ชื่ออุปกรณ์</th>
                                    <th>รูปภาพ</th>
                                    <th>ประเภท</th>
                                    <th>ราคา</th>
                                    <th>ถูกยืม</th>
                                    <th>ซ่อมอยู่</th>
                                    <th>คงเหลือ</th>
                                    <th>จำนวน</th> <!-- คอลัมน์สำหรับการใส่จำนวน -->
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>{{ $item->item_code }}</td>
                                        <td>{{ $item->item_name }}</td>
                                        <td>
                                            @if ($item->item_picture)
                                                <img src="{{ asset('storage/images/' . $item->item_picture) }}"
                                                    alt="{{ $item->item_name }}" class="img-thumbnail"
                                                    style="max-width: 100px; max-height: 100px; object-fit: cover;">
                                            @else
                                                <span>ไม่มีรูปภาพ</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->itemType->type_name }}</td>
                                        <td>{{ $item->price }} บาท</td>
                                        <td>{{ $item->borrowed_quantity }}</td>
                                        <td>{{ $item->repair_quantity }}</td>
                                        <td>{{ $item->item_quantity - $item->borrowed_quantity - $item->repair_quantity }}</td>
                                        <td>
                                            <input type="number" name="item_quantity[{{ $item->id }}]" min="1" 
                                                   max="{{ $item->item_quantity - $item->borrowed_quantity - $item->repair_quantity }}"
                                                   placeholder="จำนวน" class="form-control" style="width: 100px;">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">{{ __('ไม่พบรายการอุปกรณ์') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="submit" class="btn btn-primary" form="lendingForm">ยืนยันการยืม</button>
            </div>
        </div>
    </div>
</div>




@endsection
