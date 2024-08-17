@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h4>{{ __('การจัดการสนาม') }}</h4>
                </div>
                <div class="card-body p-3">
                    <a href="{{ route('stadiums.create') }}" class="btn btn-primary mb-3">เพิ่มสนามใหม่</a>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ชื่อสนาม</th>
                                <th>ราคา</th>
                                <th>รูปภาพ</th>
                                <th>สถานะ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stadiums as $stadium)
                            <tr>
                                <td>{{ $stadium->stadium_name }}</td>
                                <td>{{ $stadium->stadium_price }} บาท</td>
                                <td>
                                    <img src="{{ asset('storage/' . $stadium->stadium_picture) }}" alt="{{ $stadium->stadium_name }}" width="200" class="img-thumbnail">
                                </td>
                                <td>
                                    @if($stadium->stadium_status == 'พร้อมให้บริการ')
                                        <span class="badge bg-success">{{ $stadium->stadium_status }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $stadium->stadium_status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('stadiums.edit', $stadium->id) }}" class="btn btn-warning btn-sm">แก้ไข</a>
                                    <form action="{{ route('stadiums.destroy', $stadium->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
