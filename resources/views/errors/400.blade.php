<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>400 - Bad Request</title>
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
            animation: glitch 1s infinite;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
        }

        @keyframes glitch {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
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
            color: #667eea;
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

        .broken-link {
            font-size: 5rem;
            filter: drop-shadow(0 0 10px rgba(255,255,255,0.5));
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            animation: rise 10s infinite ease-in;
        }

        @keyframes rise {
            0% {
                bottom: -100px;
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            100% {
                bottom: 100%;
                opacity: 0;
            }
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

            .broken-link {
                font-size: 3.5rem;
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

            .broken-link {
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

            .broken-link {
                font-size: 4.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle" style="left: 10%; width: 10px; height: 10px; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; width: 15px; height: 15px; animation-delay: 2s;"></div>
        <div class="particle" style="left: 30%; width: 8px; height: 8px; animation-delay: 4s;"></div>
        <div class="particle" style="left: 40%; width: 12px; height: 12px; animation-delay: 1s;"></div>
        <div class="particle" style="left: 50%; width: 10px; height: 10px; animation-delay: 3s;"></div>
        <div class="particle" style="left: 60%; width: 14px; height: 14px; animation-delay: 5s;"></div>
        <div class="particle" style="left: 70%; width: 9px; height: 9px; animation-delay: 2.5s;"></div>
        <div class="particle" style="left: 80%; width: 11px; height: 11px; animation-delay: 4.5s;"></div>
        <div class="particle" style="left: 90%; width: 13px; height: 13px; animation-delay: 1.5s;"></div>
    </div>

    <div class="container">
        <div class="illustration">
            <div class="broken-link">🔗💥</div>
        </div>
        <div class="error-code">400</div>
        <h1 class="error-title">Bad Request</h1>
        <p class="error-message">
            Oops! Permintaan Anda tidak dapat diproses.<br>
            Sepertinya ada kesalahan dalam sintaks atau URL yang Anda masukkan.
        </p>
        <a href="{{ url('/') }}" class="btn-home">Kembali ke Beranda</a>
    </div>
</body>
</html>
