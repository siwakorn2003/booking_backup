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
                    <form action="{{ route('stadiums.update', $stadium->id) }}" method="POST" enctype="multipart/form-data">
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
                            <input type="number" class="form-control @error('stadium_price') is-invalid @enderror" id="stadium_price" name="stadium_price" value="{{ old('stadium_price', $stadium->stadium_price) }}" required>
                            @error('stadium_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="stadium_picture" class="form-label">รูปภาพ (ไม่จำเป็น)</label>
                            <input type="file" class="form-control @error('stadium_picture') is-invalid @enderror" id="stadium_picture" name="stadium_picture">
                            @error('stadium_picture')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($stadium->stadium_picture)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $stadium->stadium_picture) }}" alt="{{ $stadium->stadium_name }}" width="100">
                                </div>
                            @endif
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
                        
                        

                        <button type="submit" class="btn btn-primary">อัปเดตข้อมูลสนาม</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
