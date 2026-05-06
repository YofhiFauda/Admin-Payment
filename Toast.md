# Source Code Toast Interaktif & Premium
Untuk menggunakan Toast ini di project Anda, pastikan Anda sudah memasang Tailwind CSS dan Lucide Icons di dalam file HTML Anda.


## 1. CSS (Animasi & Style)

Tambahkan CSS ini ke dalam file .css Anda atau di dalam tag style pada tag head. Ini berfungsi untuk mencegah scroll tumpang-tindih dan menjalankan animasi garis waktu (progress bar).

```
/* Mencegah scroll horizontal akibat animasi Toast masuk dari kanan */
body { 
    overflow-x: hidden; 
}

#toast-container-stack {
    overflow: hidden;
    right: 0;
    z-index: 9999;
}

/* Animasi Progress Bar menyusut selama 6 detik */
@keyframes shrink {
    from { transform: scaleX(1); }
    to { transform: scaleX(0); }
}

.animate-toast-progress {
    animation: shrink 6s linear forwards;
    transform-origin: left;
}
```

## 2. JavaScript (Fungsi Utama)

Tambahkan fungsi renderToast ini ke dalam file .js Anda atau di dalam tag script. Fungsi ini menangani pembuatan elemen, pembatasan jumlah maksimal toast (anti-spam), serta logika jeda waktu saat kursor diarahkan ke toast (hover pause).

```
const renderToast = (title, message, theme, iconName) => {
    // Generate ID unik untuk setiap toast
    const toastId = 'toast-' + Date.now() + Math.floor(Math.random() * 1000);

    // Buat container jika belum ada
    let container = document.getElementById('toast-container-stack');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container-stack';
        container.className = 'fixed top-4 right-0 sm:top-6 sm:right-4 md:right-6 z-[110] flex flex-col gap-3 pointer-events-none items-center sm:items-end w-full sm:max-w-[380px] px-4 sm:px-0';
        document.body.appendChild(container);
    }

    // Batasi maksimal 5 toast agar layar tidak penuh
    if (container.children.length >= 5) {
        const oldestToast = container.firstElementChild;
        if (oldestToast) {
            oldestToast.classList.remove('opacity-100', 'translate-x-0', 'scale-100');
            oldestToast.classList.add('opacity-0', 'translate-x-full', 'scale-95');
            setTimeout(() => { if (oldestToast.parentNode) oldestToast.remove(); }, 500);
        }
    }

    // Struktur HTML Toast
    const html = `
        <div id="${toastId}" class="realtime-toast-item relative overflow-hidden pointer-events-auto flex items-start w-full sm:w-auto sm:min-w-[340px] p-4 bg-white/95 backdrop-blur-md rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-slate-100 ${theme.border} opacity-0 transform translate-x-full scale-95 transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]">
            
            <!-- Ikon -->
            <div class="inline-flex items-center justify-center flex-shrink-0 w-11 h-11 rounded-full ${theme.iconBg} ${theme.iconText} shadow-inner">
                <i data-lucide="${iconName || 'bell'}" class="w-5 h-5"></i>
            </div>
            
            <!-- Konten Teks -->
            <div class="flex-1 min-w-0 pt-0.5 ml-3.5 text-left">
                <h3 class="text-sm font-bold text-slate-800 mb-1 leading-none tracking-tight">${title}</h3>
                <p class="text-[13px] text-slate-500 font-medium leading-relaxed">${message}</p>
            </div>
            
            <!-- Tombol Tutup -->
            <button type="button" class="close-toast-btn flex-shrink-0 ms-auto -mr-1 -mt-1 bg-transparent text-slate-300 hover:text-slate-700 rounded-full p-1.5 hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>

            <!-- Progress Bar Indikator -->
            <div class="progress-bar absolute bottom-0 left-0 h-[3px] w-full ${theme.progress} animate-toast-progress"></div>
        </div>
    `;

    // Sisipkan ke dalam DOM
    container.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById(toastId);
    if (!el) return;

    // Render ikon spesifik untuk toast ini
    if (typeof lucide !== 'undefined') {
        lucide.createIcons({ root: el });
    }

    // Animasi Masuk
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            const animEl = document.getElementById(toastId);
            if (!animEl) return;
            animEl.classList.remove('opacity-0', 'translate-x-full', 'scale-95');
            animEl.classList.add('opacity-100', 'translate-x-0', 'scale-100');
        });
    });

    // Fungsi Animasi Keluar
    const removeToast = () => {
        if (el.classList.contains('removing')) return;
        el.classList.add('removing');
        el.classList.remove('opacity-100', 'translate-x-0', 'scale-100');
        el.classList.add('opacity-0', 'translate-x-full', 'scale-95');
        setTimeout(() => { if (el.parentNode) el.remove(); }, 500);
    };

    // Logika Timer & Progress Bar (Pause / Resume)
    const duration = 6000; // Toast tampil selama 6 detik
    let startTime = Date.now();
    let remainingTime = duration;
    let dismissTimeout;
    const progressBar = el.querySelector('.progress-bar');

    const startTimer = () => {
        dismissTimeout = setTimeout(removeToast, remainingTime);
        if (progressBar) progressBar.style.animationPlayState = 'running';
    };

    const pauseTimer = () => {
        clearTimeout(dismissTimeout);
        remainingTime -= (Date.now() - startTime); // Hitung sisa waktu
        if (progressBar) progressBar.style.animationPlayState = 'paused';
    };

    startTimer(); // Mulai timer

    // Event Listener: Tutup manual
    const closeBtn = el.querySelector('.close-toast-btn');
    if (closeBtn) closeBtn.addEventListener('click', () => {
        clearTimeout(dismissTimeout);
        removeToast();
    });

    // Event Listener: Hover Pause
    el.addEventListener('mouseenter', pauseTimer);
    el.addEventListener('mouseleave', () => {
        startTime = Date.now(); // Reset waktu mulai
        startTimer(); // Lanjutkan timer
    });
};

```


## 3. Cara Memanggil Fungsi

Gunakan format di bawah ini untuk memanggil toast dari bagian manapun pada aplikasi Anda:

```
// Contoh: Kondisi Berhasil (Accepts)
renderToast(
    'Berhasil Disimpan',                                      // Judul
    'Data profil Anda telah berhasil diperbarui di sistem.',  // Pesan
    {
        border: 'border-l-[5px] border-l-emerald-500',        // Warna garis kiri
        iconBg: 'bg-emerald-100',                             // Warna background ikon
        iconText: 'text-emerald-600',                         // Warna ikon
        progress: 'bg-emerald-500'                            // Warna garis progress
    },
    'check'                                                   // Nama ikon Lucide
);
```
