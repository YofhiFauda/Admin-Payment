<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Error Pages</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .error-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .error-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .error-code {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .error-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .error-type {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            color: white;
            margin-top: 0.5rem;
        }

        .client-error {
            background: #f093fb;
        }

        .server-error {
            background: #ff6b6b;
        }

        .footer {
            text-align: center;
            color: white;
            margin-top: 3rem;
            padding: 2rem;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .footer h3 {
            margin-bottom: 1rem;
        }

        .footer p {
            opacity: 0.9;
            line-height: 1.6;
        }

        .warning {
            background: rgba(255,193,7,0.2);
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 5px;
            color: white;
        }

        .warning strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .header h1 {
            animation: float 3s ease-in-out infinite;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .header p {
                font-size: 1rem;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .error-card {
                padding: 1.5rem;
            }

            .error-code {
                font-size: 2.5rem;
            }

            .error-title {
                font-size: 1.1rem;
            }

            .error-type {
                font-size: 0.75rem;
                padding: 0.25rem 0.6rem;
            }

            .footer {
                padding: 1.5rem;
                margin-top: 2rem;
            }

            .footer h3 {
                font-size: 1.2rem;
            }

            .footer p {
                font-size: 0.9rem;
            }

            .warning {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }

            .header {
                margin-bottom: 2rem;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .header p {
                font-size: 0.9rem;
            }

            .error-card {
                padding: 1.2rem;
            }

            .error-code {
                font-size: 2rem;
            }

            .error-title {
                font-size: 1rem;
            }

            .footer {
                padding: 1rem;
            }

            .footer h3 {
                font-size: 1.1rem;
            }

            .footer p {
                font-size: 0.85rem;
            }
        }

        /* Tablet */
        @media (min-width: 769px) and (max-width: 1024px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .header h1 {
                font-size: 2.5rem;
            }
        }

        /* Large Desktop */
        @media (min-width: 1440px) {
            .container {
                max-width: 1400px;
            }

            .grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎨 Preview Error Pages</h1>
            <p>Klik pada card untuk melihat preview halaman error</p>
        </div>

        <div class="warning">
            <strong>⚠️ Perhatian:</strong>
            Halaman ini hanya tersedia di environment development. Pastikan untuk menghapus atau menonaktifkan route preview ini di production!
        </div>

        <div class="grid">
            @foreach($errors as $error)
            <a href="{{ route('errors.preview.show', $error['code']) }}" class="error-card" target="_blank">
                <div class="error-code" style="color: {{ $error['color'] }}">{{ $error['code'] }}</div>
                <div class="error-title">{{ $error['title'] }}</div>
                <span class="error-type {{ substr($error['code'], 0, 1) === '4' ? 'client-error' : 'server-error' }}">
                    {{ substr($error['code'], 0, 1) === '4' ? 'Client Error' : 'Server Error' }}
                </span>
            </a>
            @endforeach
        </div>

        <div class="footer">
            <h3>📝 Cara Menggunakan</h3>
            <p>
                <strong>1. Preview:</strong> Klik pada card di atas untuk membuka halaman error di tab baru<br>
                <strong>2. Testing:</strong> Laravel akan otomatis menampilkan halaman error yang sesuai ketika terjadi error<br>
                <strong>3. Production:</strong> Jangan lupa hapus route preview ini sebelum deploy ke production!
            </p>
        </div>
    </div>
</body>
</html>
