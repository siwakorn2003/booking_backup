{{-- @extends('layouts.app')

@section('title', 'ซ่อมอุปกรณ์')

@if (session('success'))
    <div id="success-alert" class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@section('content')
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header" style="text-align: center; color:white; background-color:#4800ff">
                        <h4>{{ __('ซ่อมอุปกรณ์') }}</h4>
                    </div>

                    <div class="card-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="borrowed-tab" data-bs-toggle="tab" href="#borrowed" role="tab">ยืมแล้ว</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="returned-tab" data-bs-toggle="tab" href="#returned" role="tab">คืนแล้ว</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="repair-tab" data-bs-toggle="tab" href="#repair" role="tab">ซ่อมอุปกรณ์</a>
                            </li>
                        </ul>

                        <div class="tab-content" id="myTabContent">
                            <!-- ยืมแล้ว -->
                            <div class="tab-pane fade show active" id="borrowed" role="tabpanel" aria-labelledby="borrowed-tab">
                                <h5 class="mt-4">อุปกรณ์ที่ยืมแล้ว</h5>
                                <table class="table table-bordered">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th>รหัสอุปกรณ์</th>
                                            <th>ชื่ออุปกรณ์</th>
                                            <th>วันที่ยืม</th>
                                            <th>สถานะ</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($borrowedItems as $item)
                                            <tr>
                                                <td>{{ $item->item_code }}</td>
                                                <td>{{ $item->item_name }}</td>
                                                <td>{{ $item->borrowed_date }}</td>
                                                <td>ยืมแล้ว</td>
                                                <td>
                                                    <form action="{{ route('repair.borrowed', $item->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-warning btn-sm"
                                                            onclick="return confirm('คุณต้องการส่งอุปกรณ์นี้ไปซ่อม?');">ส่งซ่อม</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">{{ __('ไม่พบอุปกรณ์ที่ยืมแล้ว') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- คืนแล้ว -->
                            <div class="tab-pane fade" id="returned" role="tabpanel" aria-labelledby="returned-tab">
                                <h5 class="mt-4">อุปกรณ์ที่คืนแล้ว</h5>
                                <table class="table table-bordered">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th>รหัสอุปกรณ์</th>
                                            <th>ชื่ออุปกรณ์</th>
                                            <th>วันที่คืน</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($returnedItems as $item)
                                            <tr>
                                                <td>{{ $item->item_code }}</td>
                                                <td>{{ $item->item_name }}</td>
                                                <td>{{ $item->returned_date }}</td>
                                                <td>คืนแล้ว</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">{{ __('ไม่พบอุปกรณ์ที่คืนแล้ว') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- ซ่อมอุปกรณ์ -->
                            <div class="tab-pane fade" id="repair" role="tabpanel" aria-labelledby="repair-tab">
                                <h5 class="mt-4">อุปกรณ์ที่อยู่ในสถานะซ่อม</h5>
                                <table class="table table-bordered">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th>รหัสอุปกรณ์</th>
                                            <th>ชื่ออุปกรณ์</th>
                                            <th>วันที่ซ่อม</th>
                                            <th>สถานะ</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($repairItems as $item)
                                            <tr>
                                                <td>{{ $item->item_code }}</td>
                                                <td>{{ $item->item_name }}</td>
                                                <td>{{ $item->repair_date }}</td>
                                                <td>ซ่อมอยู่</td>
                                                <td>
                                                    <form action="{{ route('repair.complete', $item->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm"
                                                            onclick="return confirm('คุณต้องการยืนยันการซ่อมเสร็จสิ้น?');">ยืนยันซ่อมเสร็จ</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">{{ __('ไม่พบอุปกรณ์ที่อยู่ในสถานะซ่อม') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .nav-tabs .nav-link.active {
                background-color: #4800ff;
                color: white;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            // ฟังก์ชันสำหรับซ่อนข้อความแจ้งเตือนหลังจาก 5 วินาที
            setTimeout(function() {
                const alert = document.getElementById('success-alert');
                if (alert) {
                    alert.style.display = 'none'; // ซ่อนข้อความแจ้งเตือน
                }
            }, 5000); // 5000 milliseconds = 5 seconds
        </script>
    @endpush
@endsection --}}
