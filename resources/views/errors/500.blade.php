<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
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
            animation: shake 0.5s ease-in-out infinite;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
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
            color: #ff6b6b;
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
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .server-icon {
            font-size: 6rem;
            filter: drop-shadow(0 0 20px rgba(255,255,255,0.5));
        }

        .sparks {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .spark {
            position: absolute;
            font-size: 2rem;
            animation: sparkle 2s ease-in-out infinite;
        }

        @keyframes sparkle {
            0%, 100% {
                opacity: 0;
                transform: scale(0) rotate(0deg);
            }
            50% {
                opacity: 1;
                transform: scale(1) rotate(180deg);
            }
        }

        .status-message {
            margin-top: 2rem;
            padding: 1rem 2rem;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            animation: fadeInUp 1.4s ease-out;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
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

            .server-icon {
                font-size: 4rem;
            }

            .btn-home {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }

            .status-message {
                padding: 0.8rem 1.5rem;
                margin: 2rem 1rem 0;
                font-size: 0.95rem;
            }

            .spark {
                font-size: 1.5rem;
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

            .server-icon {
                font-size: 3rem;
            }

            .btn-home {
                padding: 0.7rem 1.5rem;
                font-size: 0.95rem;
            }

            .status-message {
                padding: 0.7rem 1.2rem;
                font-size: 0.9rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .error-code {
                font-size: 6.5rem;
            }

            .server-icon {
                font-size: 5rem;
            }
        }
    </style>
</head>
<body>
    <div class="sparks">
        <div class="spark" style="top: 20%; left: 15%; animation-delay: 0s;">⚡</div>
        <div class="spark" style="top: 40%; left: 80%; animation-delay: 0.5s;">💥</div>
        <div class="spark" style="top: 60%; left: 30%; animation-delay: 1s;">✨</div>
        <div class="spark" style="top: 80%; left: 70%; animation-delay: 1.5s;">⚡</div>
    </div>

    <div class="container">
        <div class="illustration">
            <div class="server-icon">🖥️💥</div>
        </div>
        <div class="error-code">500</div>
        <h1 class="error-title">Internal Server Error</h1>
        <p class="error-message">
            Oops! Terjadi masalah pada server kami.<br>
            Tim teknis sedang bekerja untuk memperbaikinya.
        </p>
        <a href="{{ url('/') }}" class="btn-home">Kembali ke Beranda</a>
        
        <div class="status-message">
            🔧 Kami sedang memperbaiki masalah ini.<br>
            Mohon coba lagi dalam beberapa saat.
        </div>
    </div>
</body>
</html>
