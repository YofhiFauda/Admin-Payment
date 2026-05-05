<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Not Found</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
            font-size: 10rem;
            font-weight: bold;
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
            text-shadow: 5px 5px 0 rgba(0,0,0,0.2);
            position: relative;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-30px); }
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
            color: #4facfe;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: fadeInUp 1.2s ease-out;
        }

        .btn-home:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .illustration {
            margin-bottom: 2rem;
            animation: spin 10s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .compass {
            font-size: 6rem;
            filter: drop-shadow(0 0 20px rgba(255,255,255,0.5));
        }

        .clouds {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .cloud {
            position: absolute;
            font-size: 3rem;
            opacity: 0.3;
            animation: drift 30s linear infinite;
        }

        @keyframes drift {
            from { transform: translateX(-100px); }
            to { transform: translateX(calc(100vw + 100px)); }
        }

        .search-suggestion {
            margin-top: 2rem;
            animation: fadeInUp 1.4s ease-out;
        }

        .search-box {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: rgba(255,255,255,0.2);
            border-radius: 30px;
            backdrop-filter: blur(10px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }

            .error-code {
                font-size: 6rem;
            }

            .error-title {
                font-size: 1.8rem;
            }

            .error-message {
                font-size: 1rem;
                padding: 0 1rem;
            }

            .compass {
                font-size: 4rem;
            }

            .btn-home {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }

            .search-box {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }

            .cloud {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 4.5rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .error-message {
                font-size: 0.9rem;
            }

            .compass {
                font-size: 3rem;
            }

            .btn-home {
                padding: 0.7rem 1.5rem;
                font-size: 0.95rem;
            }

            .search-box {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
        }

        /* Tablet Landscape */
        @media (min-width: 769px) and (max-width: 1024px) {
            .error-code {
                font-size: 8rem;
            }

            .error-title {
                font-size: 2.2rem;
            }

            .compass {
                font-size: 5rem;
            }
        }
    </style>
</head>
<body>
    <div class="clouds">
        <div class="cloud" style="top: 10%; animation-delay: 0s;">☁️</div>
        <div class="cloud" style="top: 30%; animation-delay: -10s;">☁️</div>
        <div class="cloud" style="top: 50%; animation-delay: -20s;">☁️</div>
        <div class="cloud" style="top: 70%; animation-delay: -15s;">☁️</div>
    </div>

    <div class="container">
        <div class="illustration">
            <div class="compass">🧭</div>
        </div>
        <div class="error-code">404</div>
        <h1 class="error-title">Halaman Tidak Ditemukan</h1>
        <p class="error-message">
            Oops! Halaman yang Anda cari sepertinya hilang di dunia maya.<br>
            Mungkin sudah dihapus atau link-nya salah.
        </p>
        <a href="{{ url('/') }}" class="btn-home">🏠 Kembali ke Beranda</a>
        
        <div class="search-suggestion">
            <div class="search-box">
                💡 Coba cek URL atau gunakan menu navigasi
            </div>
        </div>
    </div>
</body>
</html>
