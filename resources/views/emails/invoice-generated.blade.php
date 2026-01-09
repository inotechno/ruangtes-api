<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Baru</title>
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
        .alert {
            padding: 15px;
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 6px;
            margin-bottom: 20px;
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
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            text-align: center;
            padding: 20px;
            background-color: #eff6ff;
            border-radius: 6px;
            margin: 20px 0;
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

            <p>Invoice baru telah dibuat untuk perusahaan Anda.</p>

            <div class="alert">
                <strong>Jatuh Tempo:</strong> {{ $dueDate }}
            </div>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Nomor Invoice:</span>
                    <span class="info-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal Invoice:</span>
                    <span class="info-value">{{ $invoice->created_at->format('d F Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jatuh Tempo:</span>
                    <span class="info-value">{{ $dueDate }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">{{ strtoupper($invoice->status) }}</span>
                </div>
            </div>

            <div class="amount">
                Total: Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
            </div>

            <p>Silakan lakukan pembayaran sebelum tanggal jatuh tempo. Setelah pembayaran, upload bukti pembayaran melalui dashboard.</p>

            <a href="{{ $invoiceUrl }}" class="button">Lihat Detail Invoice</a>
        </div>

        <div class="footer">
            <p>Email ini dikirim otomatis oleh sistem RuangTes. Mohon jangan membalas email ini.</p>
            <p>&copy; {{ date('Y') }} RuangTes. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
