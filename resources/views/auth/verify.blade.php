<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - FinanceOps</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="font-sans antialiased min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="flex items-center justify-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
                <i data-lucide="receipt" class="w-5 h-5 text-white"></i>
            </div>
            <span class="font-bold text-xl text-slate-800 tracking-tight">FinanceOps</span>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="p-8 text-center">

                @if($status === 'success')
                    {{-- Success State --}}
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-500/20">
                        <i data-lucide="check" class="w-10 h-10 text-white"></i>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 mb-3 tracking-tight">Email Terverifikasi! 🎉</h1>
                    <p class="text-sm text-slate-500 font-medium leading-relaxed mb-8">{{ $message }}</p>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-2xl font-bold text-sm shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 transition-all active:scale-[0.98]">
                        <i data-lucide="log-in" class="w-4 h-4"></i> Login ke Dashboard
                    </a>

                @elseif($status === 'already')
                    {{-- Already Verified State --}}
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-blue-500/20">
                        <i data-lucide="info" class="w-10 h-10 text-white"></i>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 mb-3 tracking-tight">Sudah Terverifikasi</h1>
                    <p class="text-sm text-slate-500 font-medium leading-relaxed mb-8">{{ $message }}</p>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-2xl font-bold text-sm shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 transition-all active:scale-[0.98]">
                        <i data-lucide="log-in" class="w-4 h-4"></i> Login ke Dashboard
                    </a>

                @else
                    {{-- Error State --}}
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-500/20">
                        <i data-lucide="x" class="w-10 h-10 text-white"></i>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 mb-3 tracking-tight">Verifikasi Gagal</h1>
                    <p class="text-sm text-slate-500 font-medium leading-relaxed mb-8">Link verifikasi tidak valid atau sudah kadaluarsa. Silakan hubungi administrator untuk mengirim ulang email verifikasi.</p>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 px-8 py-3.5 bg-slate-800 text-white rounded-2xl font-bold text-sm hover:bg-slate-700 transition-all active:scale-[0.98]">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Login
                    </a>
                @endif

            </div>
        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-slate-400 font-medium mt-8">&copy; 2026 FinanceOps Inc.</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
    </script>
</body>
</html>
