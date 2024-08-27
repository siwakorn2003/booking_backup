@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h4>{{ __('แก้ไขข้อมูลสนาม') }}</h4>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('stadiums.update', $stadium->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="stadium_name" class="form-label">ชื่อสนาม</label>
                            <input type="text" class="form-control @error('stadium_name') is-invalid @enderror" id="stadium_name" name="stadium_name" value="{{ old('stadium_name', $stadium->stadium_name) }}" required>
                            @error('stadium_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="stadium_price" class="form-label">ราคา</label>
                            <div class="input-group">
                                <input type="number" class="form-control @error('stadium_price') is-invalid @enderror" id="stadium_price" name="stadium_price" value="{{ old('stadium_price', $stadium->stadium_price) }}" required>
                                <span class="input-group-text">บาท</span>
                            </div>
                            @error('stadium_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="stadium_status" class="form-label">สถานะ</label>
                            <select class="form-select @error('stadium_status') is-invalid @enderror" id="stadium_status" name="stadium_status" required>
                                <option value="">เลือกสถานะ</option>
                                <option value="พร้อมให้บริการ" {{ old('stadium_status', $stadium->stadium_status) == 'พร้อมให้บริการ' ? 'selected' : '' }}>พร้อมให้บริการ</option>
                                <option value="ปิดปรับปรุง" {{ old('stadium_status', $stadium->stadium_status) == 'ปิดปรับปรุง' ? 'selected' : '' }}>ปิดปรับปรุง</option>
                            </select>
                            @error('stadium_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="time_slots" class="form-label">ช่วงเวลา</label>
                            <div id="time-slots-container">
                                @foreach ($time_slots as $timeSlot)
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="time_slots[]" value="{{ $timeSlot->time_slot }}" required>
                                        <button type="button" class="btn btn-outline-danger remove-time-slot">ลบ</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-success" id="add-time-slot">เพิ่มช่วงเวลา</button>
                        </div>

                        <button type="submit" class="btn btn-primary">อัปเดตสนาม</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('time-slots-container');
        const addButton = document.getElementById('add-time-slot');

        addButton.addEventListener('click', function() {
            const div = document.createElement('div');
            div.classList.add('input-group', 'mb-2');
            div.innerHTML = `
                <input type="text" class="form-control" name="time_slots[]" placeholder="เวลา เช่น 11:00 - 12:00" required>
                <button type="button" class="btn btn-outline-danger remove-time-slot">ลบ</button>
            `;
            container.appendChild(div);
        });

        container.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-time-slot')) {
                event.target.parentElement.remove();
            }
        });
    });
</script>
@endpush
@endsection