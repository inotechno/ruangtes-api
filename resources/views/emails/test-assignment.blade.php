<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penugasan Tes - RuangTes</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .content {
            color: #4b5563;
            margin-bottom: 30px;
        }
        .test-info {
            background-color: #f9fafb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .test-info-item {
            margin-bottom: 10px;
        }
        .test-info-label {
            font-weight: 600;
            color: #374151;
            margin-right: 10px;
        }
        .test-info-value {
            color: #4b5563;
        }
        .schedule-info {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .schedule-info-title {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .schedule-info-item {
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .link-container {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 6px;
            word-break: break-all;
        }
        .link-text {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .link-url {
            font-size: 12px;
            color: #2563eb;
            word-break: break-all;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-text {
            color: #92400e;
            font-size: 14px;
        }
        .info {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-text {
            color: #1e40af;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">RuangTes</div>
            <div class="title">Anda Mendapat Penugasan Tes</div>
        </div>

        <div class="content">
            <p>Halo <strong>{{ $participant->name }}</strong>,</p>

            <p>Anda telah ditugaskan untuk mengerjakan tes berikut:</p>

            <div class="test-info">
                <div class="test-info-item">
                    <span class="test-info-label">Nama Tes:</span>
                    <span class="test-info-value">{{ $test->name }}</span>
                </div>
                <div class="test-info-item">
                    <span class="test-info-label">Kode Tes:</span>
                    <span class="test-info-value">{{ $test->code }}</span>
                </div>
                <div class="test-info-item">
                    <span class="test-info-label">Durasi:</span>
                    <span class="test-info-value">{{ $durationMinutes }} menit</span>
                </div>
                @if($test->description)
                <div class="test-info-item">
                    <span class="test-info-label">Deskripsi:</span>
                    <span class="test-info-value">{{ $test->description }}</span>
                </div>
                @endif
            </div>

            <div class="schedule-info">
                <div class="schedule-info-title">📅 Periode Pengerjaan:</div>
                <div class="schedule-info-item">• Mulai: {{ $startDate }}</div>
                <div class="schedule-info-item">• Selesai: {{ $endDate }}</div>
            </div>

            <div class="info">
                <div class="info-text">
                    <strong>Informasi Penting:</strong><br>
                    • Silakan klik tombol di bawah untuk mengakses tes<br>
                    • Pastikan Anda memiliki koneksi internet yang stabil<br>
                    • Tes harus dikerjakan dalam periode yang ditentukan<br>
                    • Link tes ini unik dan hanya dapat digunakan oleh Anda
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $testLink }}" class="button">Mulai Tes</a>
            </div>

            <div class="link-container">
                <div class="link-text">Atau salin dan tempel link berikut ke browser Anda:</div>
                <div class="link-url">{{ $testLink }}</div>
            </div>

            <div class="warning">
                <div class="warning-text">
                    <strong>Perhatian:</strong> Link tes ini unik dan bersifat rahasia. Jangan bagikan link ini kepada siapapun. Jika Anda merasa tidak seharusnya menerima email ini, hubungi administrator perusahaan Anda.
                </div>
            </div>

            <p>Selamat mengerjakan tes!</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} RuangTes. All rights reserved.</p>
            <p>Email ini dikirim secara otomatis, mohon jangan membalas email ini.</p>
        </div>
    </div>
</body>
</html>
