<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <title>502 - Bad Gateway</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%);
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
            animation: glitch 2s ease-in-out infinite;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
        }

        @keyframes glitch {
            0%, 100% { 
                transform: translate(0);
                text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
            }
            25% { 
                transform: translate(-5px, 5px);
                text-shadow: -5px -5px 0 rgba(255,255,0,0.5);
            }
            50% { 
                transform: translate(5px, -5px);
                text-shadow: 5px 5px 0 rgba(0,255,255,0.5);
            }
            75% { 
                transform: translate(-5px, -5px);
                text-shadow: -5px 5px 0 rgba(255,0,255,0.5);
            }
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
            color: #f857a6;
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

        .gateway-icon {
            font-size: 6rem;
            animation: disconnect 2s ease-in-out infinite;
            filter: drop-shadow(0 0 20px rgba(255,255,255,0.5));
        }

        @keyframes disconnect {
            0%, 100% { 
                transform: translateX(0);
                opacity: 1;
            }
            50% { 
                transform: translateX(20px);
                opacity: 0.5;
            }
        }

        .connection-line {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 4px;
            background: rgba(255,255,255,0.3);
            animation: blink 1s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }

        .network-nodes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .node {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255,255,255,0.5);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 0.5;
            }
            50% { 
                transform: scale(1.5);
                opacity: 1;
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

            .gateway-icon {
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

            .gateway-icon {
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

            .gateway-icon {
                font-size: 5rem;
            }
        }
    </style>
</head>
<body>
    <div class="network-nodes">
        <div class="node" style="top: 10%; left: 20%; animation-delay: 0s;"></div>
        <div class="node" style="top: 30%; left: 70%; animation-delay: 0.5s;"></div>
        <div class="node" style="top: 60%; left: 40%; animation-delay: 1s;"></div>
        <div class="node" style="top: 80%; left: 80%; animation-delay: 1.5s;"></div>
    </div>

    <div class="container">
        <div class="illustration">
            <div class="gateway-icon">🌐❌🖥️</div>
        </div>
        <div class="error-code">502</div>
        <h1 class="error-title">Bad Gateway</h1>
        <p class="error-message">
            Server menerima respons tidak valid dari server lain.<br>
            Koneksi antar server sedang bermasalah.
        </p>
        <a href="{{ url('/') }}" class="btn-home">Kembali ke Beranda</a>
    </div>
</body>
</html>
