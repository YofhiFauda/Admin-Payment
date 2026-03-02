@extends('layouts.app')

@section('page-title', 'Notifikasi')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-6xl mx-auto relative min-h-screen font-sans">
    
    {{-- Background Effect (Optional) --}}
    <div class="fixed top-0 right-0 w-96 h-96 bg-blue-500/10 rounded-full mix-blend-multiply filter blur-[100px] pointer-events-none anim-pulse-slow"></div>
    <div class="fixed bottom-0 left-0 w-96 h-96 bg-indigo-500/10 rounded-full mix-blend-multiply filter blur-[100px] pointer-events-none anim-pulse-slow delay-1000"></div>

    <!-- Header Section -->
    <div class="relative z-10 flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4 anim-slide-down">
        <div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight mb-2">Pusat Notifikasi</h1>
            <p class="text-sm md:text-base text-slate-500 font-medium whitespace-normal sm:whitespace-nowrap">Pantau pemberitahuan OCR, status transaksi, dan aktivitas terbaru.</p>
        </div>
        
        @if($stats['unread'] > 0)
        <form action="{{ route('notifications.readAll') }}" method="POST" class="w-full sm:w-auto mt-2 sm:mt-0 shrink-0">
            @csrf
            <input type="hidden" name="type" value="{{ request('type') }}">
            <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 bg-white text-indigo-600 rounded-xl font-bold text-sm border border-indigo-100 shadow-lg shadow-indigo-200/50 hover:shadow-xl hover:shadow-indigo-300/50 hover:-translate-y-0.5 transition-all duration-200" title="Tandai semua telah dibaca">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Tandai Semua Dibaca
            </button>
        </form>
        @endif
    </div>

    <!-- Filters -->
    <div class="relative z-10 bg-white/60 backdrop-blur-xl rounded-2xl shadow-sm border border-white/60 mb-6 p-2 flex overflow-x-auto scrollbar-hide anim-slide-up delay-100">
        @php $currentType = request('type', 'all'); $currentRead = request('read'); @endphp
        
        <a href="{{ route('notifications.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition-all {{ $currentType === 'all' && !$currentRead ? 'bg-slate-800 text-white shadow-md' : 'text-slate-500 hover:bg-white hover:shadow-sm' }}">
            Semua <span class="ml-1 px-2 py-0.5 rounded-md text-[10px] {{ $currentType === 'all' && !$currentRead ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ route('notifications.index', ['read' => 'unread']) }}" class="px-5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition-all {{ $currentRead === 'unread' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-slate-500 hover:bg-white hover:shadow-sm' }}">
            Belum Dibaca <span class="ml-1 px-2 py-0.5 rounded-md text-[10px] {{ $currentRead === 'unread' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-600' }}">{{ $stats['unread'] }}</span>
        </a>
        <a href="{{ route('notifications.index', ['type' => 'ocr']) }}" class="px-5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition-all {{ $currentType === 'ocr' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-500 hover:bg-white hover:shadow-sm' }}">
            AI / OCR <span class="ml-1 px-2 py-0.5 rounded-md text-[10px] {{ $currentType === 'ocr' ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-600' }}">{{ $stats['ocr'] }}</span>
        </a>
    </div>

    @if(session('success'))
        <div class="relative z-10 mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-bold flex items-center gap-3 anim-fade-in shadow-sm">
            <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
            </div>
            {{ session('success') }}
        </div>
    @endif

    <!-- Lists -->
    <div class="relative z-10 space-y-4" id="notifications-list-container">
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
            
            <div class="group anim-slide-up {{ $delayClass }} block bg-white/80 backdrop-blur-md rounded-2xl border {{ $isRead ? 'border-slate-100 hover:border-slate-200 hover:shadow-sm' : $theme['border'].' shadow-md hover:shadow-lg' }} p-4 md:p-5 transition-all duration-300 {{ $isRead ? 'opacity-80 hover:opacity-100' : 'hover:-translate-y-1' }} cursor-pointer relative overflow-hidden" onclick="visitUrl('{{ $data['url'] ?? '' }}', '{{ $notif->id }}')">
                
                {{-- Decorative side band for unread --}}
                @if(!$isRead)
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $theme['bg'] }}"></div>
                @endif
                
                <div class="flex flex-col sm:flex-row gap-4 md:gap-5 items-start">
                    
                    <!-- Icon -->
                    <div class="flex-shrink-0 z-10 pt-1">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center {{ $theme['light'] }} {{ $theme['text'] }} {{ !$isRead ? 'shadow-inner' : '' }} transition-transform group-hover:scale-110 duration-300">
                            <i data-lucide="{{ $icon }}" class="w-6 h-6 md:w-7 md:h-7 {{ !$isRead ? 'drop-shadow-sm' : '' }}"></i>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-grow z-10 min-w-0 w-full">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-2 mb-2">
                            <div class="flex flex-wrap items-center gap-2 max-w-full">
                                <h3 class="font-bold text-slate-800 text-base md:text-lg truncate md:whitespace-normal group-hover:text-blue-600 transition-colors break-words">{{ $data['title'] ?? 'Notifikasi' }}</h3>
                                @if(!$isRead)
                                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-600 text-[10px] font-black uppercase tracking-wider shadow-sm animate-pulse-slow whitespace-nowrap hidden sm:inline-block">Baru</span>
                                    <span class="sm:hidden w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                @endif
                            </div>
                            
                            <div class="flex items-center gap-1.5 text-[10px] md:text-xs font-bold text-slate-400 whitespace-nowrap shrink-0 mt-1 md:mt-0">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                <span>{{ $notif->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        
                        <p class="text-slate-600 text-sm leading-relaxed mb-4 line-clamp-3 md:line-clamp-none pr-0 md:pr-12 break-words">{{ $data['message'] ?? '' }}</p>
                        
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-auto">
                            <div class="flex items-center gap-2">
                                @if(isset($data['invoice_number']))
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-[10px] md:text-xs font-bold uppercase tracking-wider">
                                        <i data-lucide="hash" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $data['invoice_number'] }}
                                    </span>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 justify-end transition-all duration-300 sm:opacity-0 sm:-translate-x-4 sm:group-hover:opacity-100 sm:group-hover:translate-x-0 relative z-20">
                                @if(!$isRead)
                                    <form action="{{ route('notifications.read', $notif->id) }}" method="POST" onclick="event.stopPropagation();">
                                        @csrf
                                        <button type="submit" class="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50 rounded-xl md:rounded-2xl shadow-sm transition-all hover:scale-105" title="Tandai Dibaca">
                                            <i data-lucide="check" class="w-4 h-4 text-sm"></i>
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('notifications.destroy', $notif->id) }}" method="POST" onsubmit="return confirm('Hapus notifikasi ini secara permanen?')" onclick="event.stopPropagation();">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:text-rose-600 hover:border-rose-300 hover:bg-rose-50 rounded-xl md:rounded-2xl shadow-sm transition-all hover:scale-105" title="Hapus Permanen">
                                        <i data-lucide="trash-2" class="w-4 h-4 text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="anim-fade-in delay-200 bg-white/60 backdrop-blur-md rounded-3xl border border-white/60 p-8 md:p-12 text-center shadow-sm">
                <div class="w-20 h-20 md:w-24 md:h-24 bg-slate-50 border-2 border-slate-100 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                    <i data-lucide="bell-off" class="w-10 h-10 md:w-12 h-12 text-slate-300"></i>
                </div>
                <h3 class="text-lg md:text-xl font-bold text-slate-800 mb-2">Belum Ada Notifikasi</h3>
                <p class="text-slate-500 text-xs md:text-sm max-w-sm mx-auto">Kami akan memberitahu Anda di sini untuk setiap pembaruan OCR, persetujuan nota, atau aktivitas penting lainnya.</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-8 flex justify-center pb-8 anim-slide-up delay-700">
            <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 p-2 inline-block">
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
</style>
@endpush

@push('scripts')
<script>
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
                
                const headerClass = '.relative.z-10.flex.flex-col.sm\\:flex-row.justify-between.items-start.sm\\:items-end';
                const newHeader = doc.querySelector(headerClass);
                const oldHeader = document.querySelector(headerClass);
                if (newHeader && oldHeader) {
                    oldHeader.innerHTML = newHeader.innerHTML;
                }
                
                const filterClass = '.relative.z-10.bg-white\\/60.backdrop-blur-xl.rounded-2xl';
                const newFilters = doc.querySelector(filterClass);
                const oldFilters = document.querySelector(filterClass);
                if (newFilters && oldFilters) {
                    oldFilters.innerHTML = newFilters.innerHTML;
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
            window.Echo.private(`App.Models.User.${userId}`)
                .notification((notification) => {
                    window.handleRealtimeNotification(notification);
                });
        }
    });

</script>
@endpush
@endsection