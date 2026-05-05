<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <title>403 - Forbidden</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
            animation: bounce 2s ease-in-out infinite;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
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

        .btn-home {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: white;
            color: #fa709a;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: fadeInUp 1.2s ease-out;
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .illustration {
            margin-bottom: 2rem;
            position: relative;
        }

        .stop-sign {
            font-size: 6rem;
            animation: swing 1.5s ease-in-out infinite;
            filter: drop-shadow(0 0 20px rgba(255,255,255,0.5));
        }

        @keyframes swing {
            0%, 100% { transform: rotate(-5deg); }
            50% { transform: rotate(5deg); }
        }

        .warning-stripes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 50px,
                rgba(255,255,255,0.05) 50px,
                rgba(255,255,255,0.05) 100px
            );
            z-index: 1;
            animation: slide 20s linear infinite;
        }

        @keyframes slide {
            0% { transform: translateX(0); }
            100% { transform: translateX(70px); }
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

            .stop-sign {
                font-size: 4rem;
            }

            .btn-home {
                padding: 0.8rem 2rem;
                font-size: 1rem;
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

            .stop-sign {
                font-size: 3rem;
            }

            .btn-home {
                padding: 0.7rem 1.5rem;
                font-size: 0.95rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .error-code {
                font-size: 6.5rem;
            }

            .stop-sign {
                font-size: 5rem;
            }
        }
    </style>
</head>
<body>
    <div class="warning-stripes"></div>

    <div class="container">
        <div class="illustration">
            <div class="stop-sign">🚫</div>
        </div>
        <div class="error-code">403</div>
        <h1 class="error-title">Forbidden</h1>
        <p class="error-message">
            Akses ditolak! Anda tidak memiliki izin untuk mengakses halaman ini.<br>
            Hubungi administrator jika Anda merasa ini adalah kesalahan.
        </p>
        <a href="{{ url('/') }}" class="btn-home">Kembali ke Beranda</a>
    </div>
</body>
</html>
