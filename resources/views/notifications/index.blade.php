@extends('layouts.app')

@section('page-title', 'Notifikasi')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-7xl mx-auto relative min-h-screen font-sans">
    
    {{-- Background Effect (Optional) --}}
    <div class="fixed top-0 right-0 w-96 h-96 bg-blue-500/10 rounded-full mix-blend-multiply filter blur-[100px] pointer-events-none anim-pulse-slow"></div>
    <div class="fixed bottom-0 left-0 w-96 h-96 bg-indigo-500/10 rounded-full mix-blend-multiply filter blur-[100px] pointer-events-none anim-pulse-slow delay-1000"></div>

    <!-- Header Section -->
    <div class="relative z-10 flex flex-col md:flex-row lg:flex-row justify-between items-start md:items-end lg:items-end mb-8 gap-4 anim-slide-down">
        <div>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-slate-800 tracking-tight mb-2">Pusat Notifikasi</h1>
            <p class="text-sm md:text-base lg:text-lg text-slate-500 font-medium">Pantau pemberitahuan OCR, status transaksi, dan aktivitas terbaru.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row md:flex-row gap-2 md:gap-3 w-full sm:w-auto md:w-auto mt-2 sm:mt-0 shrink-0">
            @if($stats['unread'] > 0)
            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="{{ request('type') }}">
                <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-3 md:py-3.5 bg-white text-indigo-600 rounded-xl font-bold text-sm md:text-base border border-indigo-100 shadow-lg shadow-indigo-200/50 hover:shadow-xl hover:shadow-indigo-300/50 hover:-translate-y-0.5 active:scale-95 transition-all duration-200" title="Tandai semua telah dibaca">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="hidden md:inline">Tandai Semua Dibaca</span>
                    <span class="md:hidden">Tandai Dibaca</span>
                </button>
            </form>
            @endif

            @if($stats['total'] > 0)
            <button type="button" 
                    onclick="confirmDestroyAll()"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-3 md:py-3.5 bg-white text-rose-600 rounded-xl font-bold text-sm md:text-base border border-rose-100 shadow-lg shadow-rose-200/50 hover:shadow-xl hover:shadow-rose-300/50 hover:-translate-y-0.5 active:scale-95 transition-all duration-200" title="Hapus semua notifikasi">
                <i data-lucide="trash-2" class="w-4 h-4 md:w-5 md:h-5"></i>
                <span class="hidden md:inline">Hapus Semua</span>
                <span class="md:hidden">Hapus</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Filters with Gradient Overflow Indicators -->
    <div class="relative z-10 mb-6 anim-slide-up delay-100">
        <div class="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-white/80 to-transparent pointer-events-none z-10 rounded-l-2xl"></div>
        <div class="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-white/80 to-transparent pointer-events-none z-10 rounded-r-2xl"></div>
        
        <div class="bg-white/60 backdrop-blur-xl rounded-2xl shadow-sm border border-white/60 p-2 md:p-3 flex overflow-x-auto scrollbar-hide gap-2 md:gap-3">
            @php $currentType = request('type', 'all'); $currentRead = request('read'); @endphp
            
            <a href="{{ route('notifications.index') }}" class="px-5 py-2.5 md:px-6 md:py-3 rounded-xl text-sm md:text-base font-bold whitespace-nowrap transition-all {{ $currentType === 'all' && !$currentRead ? 'bg-slate-800 text-white shadow-md' : 'text-slate-500 hover:bg-white hover:shadow-sm' }}">
                Semua <span class="ml-1.5 px-2 py-0.5 md:px-2.5 md:py-1 rounded-md text-[10px] md:text-xs {{ $currentType === 'all' && !$currentRead ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $stats['total'] }}</span>
            </a>
            <a href="{{ route('notifications.index', ['read' => 'unread']) }}" class="px-5 py-2.5 md:px-6 md:py-3 rounded-xl text-sm md:text-base font-bold whitespace-nowrap transition-all {{ $currentRead === 'unread' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-slate-500 hover:bg-white hover:shadow-sm' }}">
                Belum Dibaca <span class="ml-1.5 px-2 py-0.5 md:px-2.5 md:py-1 rounded-md text-[10px] md:text-xs {{ $currentRead === 'unread' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-600' }}">{{ $stats['unread'] }}</span>
            </a>
            <a href="{{ route('notifications.index', ['type' => 'ocr']) }}" class="px-5 py-2.5 md:px-6 md:py-3 rounded-xl text-sm md:text-base font-bold whitespace-nowrap transition-all {{ $currentType === 'ocr' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-500 hover:bg-white hover:shadow-sm' }}">
                AI / OCR <span class="ml-1.5 px-2 py-0.5 md:px-2.5 md:py-1 rounded-md text-[10px] md:text-xs {{ $currentType === 'ocr' ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-600' }}">{{ $stats['ocr'] }}</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="relative z-10 mb-6 p-4 md:p-5 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm md:text-base font-bold flex items-center gap-3 anim-fade-in shadow-sm">
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                <i data-lucide="check" class="w-4 h-4 md:w-5 md:h-5 text-emerald-600"></i>
            </div>
            {{ session('success') }}
        </div>
    @endif

    <!-- Lists -->
    <div class="relative z-10 space-y-4 md:space-y-5" id="notifications-list-container">
        @forelse($notifications as $index => $notif)
            @php
                $isRead = !is_null($notif->read_at);
                $data = $notif->data;
                $color = $data['color'] ?? 'blue';
                $icon = $data['icon'] ?? 'bell';
                
                // Color Themes
                $theme = match($color) {
                    'green'  => ['bg' => 'bg-emerald-500', 'light' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'border' => 'border-emerald-100'],
                    'red'    => ['bg' => 'bg-rose-500', 'light' => 'bg-rose-50', 'text' => 'text-rose-600', 'border' => 'border-rose-100'],
                    'indigo' => ['bg' => 'bg-indigo-500', 'light' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'border' => 'border-indigo-100'],
                    'amber'  => ['bg' => 'bg-amber-500', 'light' => 'bg-amber-50', 'text' => 'text-amber-600', 'border' => 'border-amber-100'],
                    default  => ['bg' => 'bg-blue-500', 'light' => 'bg-blue-50', 'text' => 'text-blue-600', 'border' => 'border-blue-100'],
                };
                
                $delayClass = 'delay-'.(min(($index % 10) * 100 + 200, 1000));
            @endphp
            
            <div id="notif-{{ $notif->id }}" class="group anim-slide-up {{ $delayClass }} block bg-white/80 backdrop-blur-md rounded-2xl md:rounded-3xl border {{ $isRead ? 'border-slate-100 hover:border-slate-200 hover:shadow-sm' : $theme['border'].' shadow-md hover:shadow-lg md:shadow-lg md:hover:shadow-xl' }} p-5 md:p-6 transition-all duration-300 {{ $isRead ? 'opacity-80 hover:opacity-100' : 'hover:-translate-y-1' }} cursor-pointer relative overflow-hidden" onclick="visitUrl('{{ $data['url'] ?? '' }}', '{{ $notif->id }}')">
                
                {{-- Decorative side band for unread --}}
                @if(!$isRead)
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 md:w-2 {{ $theme['bg'] }}"></div>
                @endif
                
                <div class="flex flex-row gap-4 md:gap-6 items-start">
                    
                    <!-- Icon -->
                    <div class="flex-shrink-0 z-10 pt-0.5">
                        <div class="w-12 h-12 md:w-16 md:h-16 lg:w-20 lg:h-20 rounded-xl md:rounded-2xl flex items-center justify-center {{ $theme['light'] }} {{ $theme['text'] }} {{ !$isRead ? 'shadow-inner' : '' }} transition-transform group-hover:scale-105 duration-300">
                            <i data-lucide="{{ $icon }}" class="w-6 h-6 md:w-8 md:h-8 lg:w-10 lg:h-10 {{ !$isRead ? 'drop-shadow-sm' : '' }}"></i>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-grow z-10 min-w-0">
                        <div class="flex justify-between items-start gap-3 mb-2">
                            <div class="flex flex-wrap items-center gap-2 min-w-0">
                                <h3 class="font-bold text-slate-800 text-base md:text-lg lg:text-xl group-hover:text-blue-600 transition-colors break-words">{{ $data['title'] ?? 'Notifikasi' }}</h3>
                                @if(!$isRead)
                                    <span class="px-2 py-0.5 md:px-2.5 md:py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-600 text-[9px] md:text-[10px] font-black uppercase tracking-wider shadow-sm animate-pulse-slow whitespace-nowrap">Baru</span>
                                @endif
                            </div>
                            
                            <div class="flex items-center gap-1.5 text-xs md:text-sm font-bold text-slate-400 whitespace-nowrap shrink-0 pt-1">
                                <i data-lucide="clock" class="w-3 h-3 md:w-4 md:h-4"></i>
                                <span>{{ $notif->created_at->diffForHumans(null, true, true) }}</span>
                            </div>
                        </div>
                        
                        <p class="text-slate-600 text-sm md:text-base leading-relaxed mb-3 md:mb-4 break-words">{{ $data['message'] ?? '' }}</p>
                        
                        <div class="flex flex-row items-center justify-between gap-3 mt-auto">
                            <div class="flex items-center gap-2 min-w-0">
                                @if(isset($data['invoice_number']))
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 md:px-3 md:py-1.5 bg-slate-50 text-slate-500 border border-slate-100 rounded-md text-xs md:text-sm font-bold uppercase tracking-wider truncate">
                                        <i data-lucide="hash" class="w-3 h-3 md:w-4 md:h-4 text-slate-400"></i>
                                        {{ $data['invoice_number'] }}
                                    </span>
                                @endif
                            </div>

                            <!-- Actions - Always visible on tablet for better touch interaction -->
                            <div class="flex items-center gap-2 md:gap-2.5 justify-end transition-all duration-300 relative z-20 shrink-0">
                                @if(!$isRead)
                                    <form action="{{ route('notifications.read', $notif->id) }}" method="POST" onclick="event.stopPropagation();">
                                        @csrf
                                        <button type="submit" class="w-9 h-9 md:w-11 md:h-11 lg:w-12 lg:h-12 flex items-center justify-center bg-white border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50 active:scale-95 rounded-xl md:rounded-2xl shadow-sm transition-all" title="Tandai Dibaca">
                                            <i data-lucide="check" class="w-4 h-4 md:w-5 md:h-5"></i>
                                        </button>
                                    </form>
                                @endif

                                <button type="button" 
                                        onclick="event.stopPropagation(); confirmDestroy('{{ $notif->id }}')"
                                        class="w-9 h-9 md:w-11 md:h-11 lg:w-12 lg:h-12 flex items-center justify-center bg-white border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-300 hover:bg-rose-50 active:scale-95 rounded-xl md:rounded-2xl shadow-sm transition-all" title="Hapus Permanen">
                                    <i data-lucide="trash-2" class="w-4 h-4 md:w-5 md:h-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="anim-fade-in delay-200 bg-white/60 backdrop-blur-md rounded-3xl border border-white/60 p-10 md:p-16 text-center shadow-sm">
                <div class="w-24 h-24 md:w-32 md:h-32 bg-slate-50 border-2 border-slate-100 rounded-full flex items-center justify-center mx-auto mb-6 md:mb-8 shadow-inner">
                    <i data-lucide="bell-off" class="w-12 h-12 md:w-16 h-16 text-slate-300"></i>
                </div>
                <h3 class="text-xl md:text-2xl font-bold text-slate-800 mb-3">Belum Ada Notifikasi</h3>
                <p class="text-slate-500 text-sm md:text-base max-w-md mx-auto">Kami akan memberitahu Anda di sini untuk setiap pembaruan OCR, persetujuan nota, atau aktivitas penting lainnya.</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-8 md:mt-12 flex justify-center pb-8 anim-slide-up delay-700">
            <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 p-3 md:p-4 inline-block">
                {{ $notifications->withQueryString()->links() }}
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    @keyframes slideUpFade {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideDownFade {
        0% { opacity: 0; transform: translateY(-20px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        0% { opacity: 0; }
        100% { opacity: 1; }
    }
    @keyframes pulseSlow {
        0% { transform: scale(1); opacity: 0.3; }
        50% { transform: scale(1.05); opacity: 0.5; }
        100% { transform: scale(1); opacity: 0.3; }
    }
    
    .anim-slide-up { animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    .anim-slide-down { animation: slideDownFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    .anim-fade-in { animation: fadeIn 0.6s ease-out forwards; opacity: 0; }
    .anim-pulse-slow { animation: pulseSlow 8s ease-in-out infinite; }
    .animate-pulse-slow { animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    
    .delay-100 { animation-delay: 100ms; }
    .delay-200 { animation-delay: 200ms; }
    .delay-300 { animation-delay: 300ms; }
    .delay-400 { animation-delay: 400ms; }
    .delay-500 { animation-delay: 500ms; }
    .delay-600 { animation-delay: 600ms; }
    .delay-700 { animation-delay: 700ms; }
    .delay-800 { animation-delay: 800ms; }
    .delay-900 { animation-delay: 900ms; }
    .delay-1000 { animation-delay: 1000ms; }

    /* Custom Scrollbar for Filters */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Tablet-specific touch improvements */
    @media (min-width: 768px) and (max-width: 1024px) {
        /* Ensure touch targets are at least 44x44px */
        button, a {
            min-height: 44px;
        }
        
        /* Improve readability on tablet */
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function confirmDestroyAll() {
        openConfirmModal('globalConfirmModal', {
            title: 'Hapus SEMUA Notifikasi?',
            message: 'Apakah Anda yakin ingin menghapus <strong>SEMUA</strong> notifikasi? Tindakan ini tidak dapat dibatalkan.',
            action: "{{ route('notifications.destroyAll') }}",
            method: 'DELETE',
            submitText: 'Ya, Hapus Semua',
            onConfirm: async () => {
                try {
                    const response = await fetch("{{ route('notifications.destroyAll') }}", {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    });
                    const result = await response.json();
                    if (response.ok) {
                        showToast(result.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        throw new Error(result.message || 'Gagal menghapus semua notifikasi');
                    }
                } catch (err) {
                    showToast(err.message, 'error');
                }
            }
        });
    }

    function confirmDestroy(id) {
        openConfirmModal('globalConfirmModal', {
            title: 'Hapus Notifikasi?',
            message: 'Hapus notifikasi ini secara permanen?',
            action: `/notifications/${id}`,
            method: 'DELETE',
            submitText: 'Ya, Hapus',
            onConfirm: async () => {
                try {
                    const response = await fetch(`/notifications/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    });
                    const result = await response.json();
                    if (response.ok) {
                        showToast(result.message, 'success');
                        const el = document.getElementById(`notif-${id}`);
                        if (el) {
                            el.style.opacity = '0';
                            el.style.transform = 'translateY(10px)';
                            el.style.transition = 'all 0.3s ease';
                            setTimeout(() => el.remove(), 300);
                        }
                    } else {
                        throw new Error(result.message || 'Gagal menghapus notifikasi');
                    }
                } catch (err) {
                    showToast(err.message, 'error');
                }
            }
        });
    }

    // ─────────────────────────────────────────────────────────
    // REALTIME ECHO HANDLER (NOTIFICATIONS)
    // ─────────────────────────────────────────────────────────
    window.handleRealtimeNotification = function(notification) {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newList = doc.querySelector('#notifications-list-container');
                const oldList = document.querySelector('#notifications-list-container');
                
                if (newList && oldList) {
                    oldList.innerHTML = newList.innerHTML;
                }
                
                const headerClass = '.relative.z-10.flex.flex-col.md\\:flex-row.lg\\:flex-row';
                const newHeader = doc.querySelector(headerClass);
                const oldHeader = document.querySelector(headerClass);
                if (newHeader && oldHeader) {
                    oldHeader.innerHTML = newHeader.innerHTML;
                }
                
                const filterSelector = '#notifications-list-container';
                const parentFilter = document.querySelector(filterSelector)?.previousElementSibling?.previousElementSibling;
                const newFilterParent = doc.querySelector(filterSelector)?.previousElementSibling?.previousElementSibling;
                if (parentFilter && newFilterParent) {
                    parentFilter.innerHTML = newFilterParent.innerHTML;
                }

                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

                // Update badge counter in navbar/sidebar
                if (typeof updateNotificationBadge === 'function') {
                    updateNotificationBadge();
                }
            });
    };
    
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.Echo !== 'undefined') {
            const userId = {{ Auth::id() }};
            
            // Standard Laravel Notifications
            window.Echo.private(`App.Models.User.${userId}`)
                .notification((notification) => {
                    window.handleRealtimeNotification(notification);
                });

            // Custom Real-time Event (NotificationReceived)
            window.Echo.private(`notifications.${userId}`)
                .listen('.notification.received', (e) => {
                    console.log('🔔 [REVERB] Notification Received:', e);
                    window.handleRealtimeNotification(e);
                });
        }
    });

</script>
@endpush
@endsection