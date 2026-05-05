<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <title>401 - Unauthorized</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            text-align: center;
            color: white;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .error-code {
            font-size: 8rem;
            font-weight: bold;
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .error-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            animation: fadeInUp 0.8s ease-out;
        }

        .error-message {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1.2s ease-out;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-login {
            background: white;
            color: #f5576c;
        }

        .btn-home {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .illustration {
            margin-bottom: 2rem;
            animation: shake 2s ease-in-out infinite;
        }

        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }

        .lock-icon {
            font-size: 6rem;
            filter: drop-shadow(0 0 20px rgba(255,255,255,0.5));
        }

        .background-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .shape {
            position: absolute;
            opacity: 0.1;
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }

            .error-code {
                font-size: 5rem;
            }

            .error-title {
                font-size: 1.8rem;
            }

            .error-message {
                font-size: 1rem;
                padding: 0 1rem;
            }

            .lock-icon {
                font-size: 4rem;
            }

            .btn-group {
                flex-direction: column;
                gap: 0.8rem;
            }

            .btn {
                padding: 0.8rem 2rem;
                font-size: 1rem;
                width: 100%;
                max-width: 300px;
            }

            .shape {
                font-size: 3rem;
            }
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 4rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .error-message {
                font-size: 0.9rem;
            }

            .lock-icon {
                font-size: 3rem;
            }

            .btn {
                padding: 0.7rem 1.5rem;
                font-size: 0.95rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .error-code {
                font-size: 6.5rem;
            }

            .lock-icon {
                font-size: 5rem;
            }
        }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape" style="top: 10%; left: 10%; font-size: 5rem;">🔒</div>
        <div class="shape" style="top: 70%; left: 80%; font-size: 4rem; animation-delay: -5s;">🔐</div>
        <div class="shape" style="top: 50%; left: 20%; font-size: 3rem; animation-delay: -10s;">🔑</div>
    </div>

    <div class="container">
        <div class="illustration">
            <div class="lock-icon">🔒</div>
        </div>
        <div class="error-code">401</div>
        <h1 class="error-title">Unauthorized</h1>
        <p class="error-message">
            Anda perlu login terlebih dahulu untuk mengakses halaman ini.<br>
            Silakan masuk dengan akun Anda untuk melanjutkan.
        </p>
        <div class="btn-group">
            <a href="{{ route('login') }}" class="btn btn-login">Login Sekarang</a>
            <a href="{{ url('/') }}" class="btn btn-home">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>
