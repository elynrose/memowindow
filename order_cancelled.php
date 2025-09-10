<?php
// order_cancelled.php - Order cancellation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Cancelled - MemoWindow</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .cancel-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            color: #0b0d12;
        }
        .cancel-icon {
            font-size: 64px;
            margin-bottom: 24px;
        }
        .cancel-title {
            font-size: 28px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: #d97706;
        }
        .cancel-message {
            font-size: 16px;
            color: #6b7280;
            margin: 0 0 32px 0;
            line-height: 1.5;
        }
        .btn-primary {
            background: #2a4df5;
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin: 8px;
        }
    </style>
</head>
<body>
    <div class="cancel-card">
        <div class="cancel-icon">😔</div>
        <h1 class="cancel-title">Order Cancelled</h1>
        <p class="cancel-message">
            Your order was cancelled. No payment was processed.
            Your MemoryWave is still saved and ready whenever you'd like to order a print.
        </p>
        
        <div style="margin-top: 32px;">
            <a href="login.html" class="btn-primary">Back to MemoWindow</a>
        </div>
        
        <div style="margin-top: 24px; font-size: 14px; color: #6b7280;">
            <p>You can always order prints later from your MemoWindow collection.</p>
        </div>
    </div>
</body>
</html>
