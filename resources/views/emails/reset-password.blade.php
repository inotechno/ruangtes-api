<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - RuangTes</title>
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
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-text {
            color: #991b1b;
            font-size: 14px;
        }
        .info {
            background-color: #dbeafe;
            border-left: 4px solid #2563eb;
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
            <div class="title">Reset Password</div>
        </div>

        <div class="content">
            <p>Halo <strong>{{ $user->name }}</strong>,</p>

            <p>Kami menerima permintaan untuk mereset password akun RuangTes Anda. Jika Anda yang meminta reset password, silakan klik tombol di bawah ini untuk melanjutkan.</p>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </div>

            <div class="link-container">
                <div class="link-text">Atau salin dan tempel link berikut ke browser Anda:</div>
                <div class="link-url">{{ $resetUrl }}</div>
            </div>

            <div class="warning">
                <div class="warning-text">
                    <strong>Peringatan Keamanan:</strong> Link reset password ini akan kedaluwarsa dalam {{ $expiresIn }}. Jika Anda tidak meminta reset password, abaikan email ini dan password Anda tidak akan berubah.
                </div>
            </div>

            <div class="info">
                <div class="info-text">
                    <strong>Tips Keamanan:</strong> Setelah berhasil mereset password, pastikan Anda menggunakan password yang kuat dan unik. Jangan bagikan password Anda kepada siapapun.
                </div>
            </div>

            <p>Jika Anda tidak meminta reset password, tidak ada tindakan yang diperlukan. Password Anda akan tetap aman.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} RuangTes. All rights reserved.</p>
            <p>Email ini dikirim secara otomatis, mohon jangan membalas email ini.</p>
            <p>Jika Anda memiliki pertanyaan, silakan hubungi tim support kami.</p>
        </div>
    </div>
</body>
</html>

