<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>408 - Request Timeout</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
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
            animation: fadeInScale 1s ease-out;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.1);
            color: #ff6b6b;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.5);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
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
            background: #ff6b6b;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255,107,107,0.3);
            animation: fadeInUp 1.2s ease-out;
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255,107,107,0.4);
            background: #ff5252;
        }

        .illustration {
            margin-bottom: 2rem;
            position: relative;
        }

        .clock {
            font-size: 6rem;
            animation: tick 1s steps(1) infinite;
            filter: drop-shadow(0 0 20px rgba(255,107,107,0.3));
        }

        @keyframes tick {
            0%, 50% { content: '⏰'; }
            51%, 100% { content: '⏱️'; }
        }

        .hourglass {
            font-size: 6rem;
            animation: flip 2s ease-in-out infinite;
            filter: drop-shadow(0 0 20px rgba(255,107,107,0.3));
        }

        @keyframes flip {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        .loading-bar {
            width: 300px;
            height: 6px;
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            margin: 2rem auto;
            overflow: hidden;
            animation: fadeInUp 1.4s ease-out;
        }

        .loading-progress {
            height: 100%;
            background: #ff6b6b;
            border-radius: 10px;
            animation: loading 3s ease-in-out infinite;
        }

        @keyframes loading {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 0%; }
        }

        .tips {
            margin-top: 2rem;
            padding: 1rem 2rem;
            background: rgba(255,255,255,0.5);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            animation: fadeInUp 1.6s ease-out;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .tips-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .tips-list {
            text-align: left;
            font-size: 0.95rem;
            color: #34495e;
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

            .hourglass {
                font-size: 4rem;
            }

            .btn-home {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }

            .loading-bar {
                width: 250px;
            }

            .tips {
                padding: 0.8rem 1.5rem;
                margin: 2rem 1rem 0;
            }

            .tips-list {
                font-size: 0.85rem;
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

            .hourglass {
                font-size: 3rem;
            }

            .btn-home {
                padding: 0.7rem 1.5rem;
                font-size: 0.95rem;
            }

            .loading-bar {
                width: 200px;
            }

            .tips {
                padding: 0.7rem 1.2rem;
            }

            .tips-title {
                font-size: 0.95rem;
            }

            .tips-list {
                font-size: 0.8rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .error-code {
                font-size: 6.5rem;
            }

            .hourglass {
                font-size: 5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="illustration">
            <div class="hourglass">⏳</div>
        </div>
        <div class="error-code">408</div>
        <h1 class="error-title">Request Timeout</h1>
        <p class="error-message">
            Koneksi terlalu lama dan server memutuskan sambungan.<br>
            Sepertinya koneksi internet Anda sedang lambat.
        </p>
        
        <div class="loading-bar">
            <div class="loading-progress"></div>
        </div>

        <a href="{{ url()->current() }}" class="btn-home">🔄 Coba Lagi</a>

        <div class="tips">
            <div class="tips-title">💡 Tips untuk mengatasi masalah ini:</div>
            <div class="tips-list">
                • Periksa koneksi internet Anda<br>
                • Refresh halaman (F5 atau Ctrl+R)<br>
                • Coba gunakan jaringan yang lebih stabil<br>
                • Tunggu beberapa saat dan coba lagi
            </div>
        </div>
    </div>
</body>
</html>
