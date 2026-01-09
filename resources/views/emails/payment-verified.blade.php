<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Diverifikasi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        .content {
            background-color: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .status {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .status.approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status.rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .info-box {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #6b7280;
        }
        .info-value {
            color: #111827;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">RuangTes</div>
        </div>

        <div class="content">
            <h2>Halo, {{ $user->name }}!</h2>

            <p>Pembayaran Anda telah diverifikasi dengan status:</p>

            <div class="status {{ $approved ? 'approved' : 'rejected' }}">
                {{ $approved ? '✓ DISETUJUI' : '✗ DITOLAK' }}
            </div>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Nomor Pembayaran:</span>
                    <span class="info-value">{{ $payment->payment_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jumlah:</span>
                    <span class="info-value">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Metode:</span>
                    <span class="info-value">{{ ucfirst($payment->method ?? 'Manual') }}</span>
                </div>
                @if($payment->paid_at)
                <div class="info-row">
                    <span class="info-label">Tanggal Verifikasi:</span>
                    <span class="info-value">{{ $payment->paid_at->format('d F Y H:i') }}</span>
                </div>
                @endif
            </div>

            @if($approved)
                <p>Pembayaran Anda telah disetujui dan diproses. Terima kasih!</p>
            @else
                <p>Pembayaran Anda ditolak. Silakan hubungi tim support untuk informasi lebih lanjut.</p>
                @if($payment->notes)
                <div class="info-box">
                    <strong>Catatan:</strong><br>
                    {{ $payment->notes }}
                </div>
                @endif
            @endif

            <a href="{{ $paymentUrl }}" class="button">Lihat Detail Pembayaran</a>
        </div>

        <div class="footer">
            <p>Email ini dikirim otomatis oleh sistem RuangTes. Mohon jangan membalas email ini.</p>
            <p>&copy; {{ date('Y') }} RuangTes. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
