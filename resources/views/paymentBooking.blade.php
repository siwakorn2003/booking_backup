 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>แจ้งชำระเงิน</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f0f0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .payment-form {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      max-width: 400px;
      width: 100%;
    }
    .form-title {
      text-align: center;
      margin-bottom: 20px;
    }
    .form-text {
      font-size: 12px;
      color: red;
      margin-top: 10px;
    }
  </style>
</head>
<body>

  <div class="payment-form">
    <h3 class="form-title">แจ้งชำระเงิน</h3>
    
    <form action="{{ route('processPayment') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="mb-3">
        <label for="bookingCode" class="form-label">รหัสการจอง*</label>
        <input type="text" class="form-control" id="bookingCode" name="booking_code" required>
      </div>
      
      <div class="mb-3">
        <label for="payerName" class="form-label">ชื่อผู้โอน*</label>
        <input type="text" class="form-control" id="payerName" name="payer_name" required>
      </div>

      <div class="mb-3">
        <label for="phoneNumber" class="form-label">เบอร์โทรศัพท์*</label>
        <input type="tel" class="form-control" id="phoneNumber" name="phone_number" required>
      </div>

      <div class="mb-3">
        <label for="paymentDateTime" class="form-label">วันที่และเวลาโอน*</label>
        <input type="datetime-local" class="form-control" id="paymentDateTime" name="payment_date_time" required>
      </div>

      <div class="mb-3">
        <label for="amount" class="form-label">จำนวนเงินที่โอน*</label>
        <input type="number" class="form-control" id="amount" name="amount" required>
      </div>

      <div class="mb-3">
        <label for="slipUpload" class="form-label">โอนแล้วอัปโหลดสลิปได้ที่</label>
        <input type="file" class="form-control" id="slipUpload" name="slip_upload" required>
      </div>

      <p class="form-text">
        เมื่อกดยืนยันแล้วไม่สามารถยกเลิกแก้ไขได้ โปรดตรวจสอบก่อนยืนยัน
      </p>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-success">ยืนยันการชำระเงิน</button>
        <button type="button" class="btn btn-secondary" onclick="window.history.back();">ยกเลิก</button>
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
