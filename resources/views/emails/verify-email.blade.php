<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - RuangTes</title>
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
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">RuangTes</div>
            <div class="title">Verifikasi Email Anda</div>
        </div>

        <div class="content">
            <p>Halo <strong>{{ $user->name }}</strong>,</p>

            <p>Terima kasih telah mendaftar di RuangTes! Untuk menyelesaikan proses pendaftaran, silakan verifikasi alamat email Anda dengan mengklik tombol di bawah ini.</p>

            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="button">Verifikasi Email</a>
            </div>

            <div class="link-container">
                <div class="link-text">Atau salin dan tempel link berikut ke browser Anda:</div>
                <div class="link-url">{{ $verificationUrl }}</div>
            </div>

            <div class="warning">
                <div class="warning-text">
                    <strong>Penting:</strong> Link verifikasi ini akan kedaluwarsa dalam {{ $expiresIn }}. Jika link sudah kedaluwarsa, Anda dapat meminta link verifikasi baru melalui aplikasi.
                </div>
            </div>

            <p>Jika Anda tidak mendaftar di RuangTes, abaikan email ini.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} RuangTes. All rights reserved.</p>
            <p>Email ini dikirim secara otomatis, mohon jangan membalas email ini.</p>
        </div>
    </div>
</body>
</html>

