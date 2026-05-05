<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <title>503 - Service Unavailable</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffa751 0%, #ffe259 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            text-align: center;
            color: #333;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .error-code {
            font-size: 8rem;
            font-weight: bold;
            margin-bottom: 1rem;
            animation: bounce 2s ease-in-out infinite;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.1);
            color: #ff6b35;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-30px); }
        }

        .error-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            animation: fadeInUp 0.8s ease-out;
            color: #2c3e50;
        }

        .error-message {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.8;
            animation: fadeInUp 1s ease-out;
            color: #34495e;
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
            background: #ff6b35;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255,107,53,0.3);
            animation: fadeInUp 1.2s ease-out;
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255,107,53,0.4);
            background: #ff5722;
        }

        .illustration {
            margin-bottom: 2rem;
            position: relative;
        }

        .maintenance-icon {
            font-size: 6rem;
            animation: rotate 3s linear infinite;
            filter: drop-shadow(0 0 20px rgba(255,107,53,0.3));
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .tools {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .tool {
            position: absolute;
            font-size: 3rem;
            opacity: 0.2;
            animation: float 15s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-50px) rotate(180deg); }
        }

        .status-box {
            margin-top: 2rem;
            padding: 1.5rem 2rem;
            background: rgba(255,255,255,0.7);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            animation: fadeInUp 1.4s ease-out;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .status-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .status-text {
            color: #34495e;
            font-size: 0.95rem;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255,107,53,0.2);
            border-radius: 10px;
            margin-top: 1rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #ff6b35;
            border-radius: 10px;
            animation: progress 3s ease-in-out infinite;
        }

        @keyframes progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 0%; }
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

            .maintenance-icon {
                font-size: 4rem;
            }

            .btn-home {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }

            .status-box {
                padding: 1.2rem 1.5rem;
                margin: 2rem 1rem 0;
            }

            .status-title {
                font-size: 1rem;
            }

            .status-text {
                font-size: 0.85rem;
            }

            .tool {
                font-size: 2rem;
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

            .maintenance-icon {
                font-size: 3rem;
            }

            .btn-home {
                padding: 0.7rem 1.5rem;
                font-size: 0.95rem;
            }

            .status-box {
                padding: 1rem 1.2rem;
            }

            .status-title {
                font-size: 0.95rem;
            }

            .status-text {
                font-size: 0.8rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .error-code {
                font-size: 6.5rem;
            }

            .maintenance-icon {
                font-size: 5rem;
            }
        }
    </style>
</head>
<body>
    <div class="tools">
        <div class="tool" style="top: 10%; left: 10%; animation-delay: 0s;">🔧</div>
        <div class="tool" style="top: 20%; left: 80%; animation-delay: -3s;">🛠️</div>
        <div class="tool" style="top: 60%; left: 20%; animation-delay: -6s;">⚙️</div>
        <div class="tool" style="top: 80%; left: 70%; animation-delay: -9s;">🔨</div>
    </div>

    <div class="container">
        <div class="illustration">
            <div class="maintenance-icon">⚙️</div>
        </div>
        <div class="error-code">503</div>
        <h1 class="error-title">Service Unavailable</h1>
        <p class="error-message">
            Server sedang dalam pemeliharaan atau terlalu sibuk.<br>
            Kami akan segera kembali!
        </p>
        
        <div class="status-box">
            <div class="status-title">🔧 Status Pemeliharaan</div>
            <div class="status-text">
                Sistem sedang dalam proses pemeliharaan untuk meningkatkan performa dan keamanan. Mohon coba lagi dalam beberapa saat.
            </div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>

        <a href="{{ url('/') }}" class="btn-home" style="margin-top: 2rem;">🔄 Coba Lagi</a>
    </div>
</body>
</html>
