<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-row {
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 0.5rem;
        }
        .form-control-sm {
            font-size: 0.875rem; /* Small font size */
        }
        .form-label {
            font-size: 0.875rem; /* Small font size */
        }
        .field-info {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Book a Football Field</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('booking.submit') }}" method="POST">
        @csrf

        <!-- Row 1: Field Info, Name, Phone, Date, Start Time, End Time and Submit Button -->
        <div class="row g-2 align-items-center">
            <!-- Field Info -->
            <div class="col-md-12 mb-3">
                <div class="field-info">สนาม 1 (7 คน) 1300 บาท/ชม.</div>
            </div>

            <!-- Name -->
            <div class="col-md-2 col-lg-1 form-group">
                <label for="name" class="form-label">ชื่อจริง</label>
                <input type="text" class="form-control form-control-sm" id="name" name="name" required>
            </div>

            <!-- Phone -->
            <div class="col-md-2 col-lg-2 form-group">
                <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                <input type="text" class="form-control form-control-sm" id="phone" name="phone" required>
            </div>

            <!-- Date -->
            <div class="col-md-2 col-lg-2 form-group">
                <label for="date" class="form-label">วันที่</label>
                <input type="date" class="form-control form-control-sm" id="date" name="date" required>
            </div>

            <!-- Start Time -->
            <div class="col-md-2 col-lg-2 form-group">
                <label for="start_time" class="form-label">เวลาเริ่ม</label>
                <input type="time" class="form-control form-control-sm" id="start_time" name="start_time" required>
            </div>

            <!-- Until Text -->
            <div class="col-md-1 form-group d-flex align-items-center justify-content-center">
                <span class="text-center">ถึง</span>
            </div>

            <!-- End Time -->
            <div class="col-md-2 col-lg-2 form-group">
                <label for="end_time" class="form-label">เวลาสิ้นสุด</label>
                <input type="time" class="form-control form-control-sm" id="end_time" name="end_time" required>
            </div>

            <!-- Submit Button -->
            <div class="col-md-2 col-lg-1 d-flex align-items-center mt-3">
                <button type="submit" class="btn btn-primary w-100">จองสนาม</button>
            </div>
        </div>
    </form>
</div>
</body>
</html>
