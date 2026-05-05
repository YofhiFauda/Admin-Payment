@extends('layouts.app')

@section('page-title', 'Notifikasi')

@section('content')
<div class="px-4 py-4 w-full max-w-7xl mx-auto relative min-h-screen font-sans">
    
    {{-- Background Effect --}}
    <div class="fixed top-0 right-0 w-96 h-96 bg-blue-400/10 rounded-full mix-blend-multiply filter blur-[100px] pointer-events-none anim-pulse-slow"></div>
    <div class="fixed bottom-0 left-0 w-96 h-96 bg-blue-400/10 rounded-full mix-blend-multiply filter blur-[100px] pointer-events-none anim-pulse-slow delay-1000"></div>

    <!-- Header Section -->
<div class="relative z-10 flex flex-col lg:flex-row justify-between items-start lg:items-end mb-8 gap-5 anim-slide-down">
    <!-- Text Content: Title & Description -->
    <div class="w-full lg:w-auto mb-4 lg:mb-0">
        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight mb-2">Pusat Notifikasi</h1>
        <p class="text-sm md:text-base text-slate-500 font-medium">Pantau pemberitahuan OCR, status transaksi, dan aktivitas terbaru.</p>
    </div>
    
    <!-- Buttons Group -->
    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto shrink-0">
        <form action="https://them-manufacturing-physics-medium.trycloudflare.com/notifications/read-all" method="POST" class="w-full sm:w-auto">
            <input type="hidden" name="_token" value="FeqcZPZqAxwd52MlvMxn7TdrLXWubOUuvwQDwpDm" autocomplete="off">
            <input type="hidden" name="type" value="">
            <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2.5 bg-white text-blue-600 rounded-xl font-bold text-sm border border-blue-100 shadow-sm hover:shadow-md hover:border-blue-200 hover:-translate-y-0.5 active:scale-95 transition-all duration-200" title="Tandai semua telah dibaca">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-lucide="check-check" aria-hidden="true" class="lucide lucide-check-check w-4 h-4"><path d="M18 6 7 17l-5-5"></path><path d="m22 10-7.5 7.5L13 16"></path></svg>
                <span class="hidden sm:inline">Tandai Semua Dibaca</span>
                <span class="sm:hidden">Baca Semua</span>
            </button>
        </form>
        
        <button type="button" onclick="confirmDestroyAll()" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2.5 bg-white text-rose-600 rounded-xl font-bold text-sm border border-rose-100 shadow-sm hover:shadow-md hover:border-rose-200 hover:-translate-y-0.5 active:scale-95 transition-all duration-200" title="Hapus semua notifikasi">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-lucide="trash-2" aria-hidden="true" class="lucide lucide-trash-2 w-4 h-4"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            <span class="hidden sm:inline">Hapus Semua</span>
            <span class="sm:hidden">Hapus</span>
        </button>
    </div>
</div>

    <!-- Filters -->
    <div class="relative z-10 mb-8 anim-slide-up delay-100">
        <div class="flex overflow-x-auto scrollbar-hide gap-3 pb-2 -mx-4 px-4 sm:mx-0 sm:px-0">
            @php $currentType = request('type', 'all'); $currentRead = request('read'); @endphp
            
            <a href="{{ route('notifications.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all {{ $currentType === 'all' && !$currentRead ? 'bg-slate-800 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200/60' }}">
                Semua
                <span class="px-2 py-0.5 rounded-md text-xs {{ $currentType === 'all' && !$currentRead ? 'bg-white/20' : 'bg-slate-100 text-slate-500' }}">{{ $stats['total'] }}</span>
            </a>
            
            <a href="{{ route('notifications.index', ['read' => 'unread']) }}" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all {{ $currentRead === 'unread' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200/60' }}">
                Belum Dibaca
                <span class="px-2 py-0.5 rounded-md text-xs {{ $currentRead === 'unread' ? 'bg-white/20' : 'bg-blue-50 text-blue-600' }}">{{ $stats['unread'] }}</span>
            </a>
            
            <a href="{{ route('notifications.index', ['type' => 'ocr']) }}" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all {{ $currentType === 'ocr' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200/60' }}">
                AI / OCR
                <span class="px-2 py-0.5 rounded-md text-xs {{ $currentType === 'ocr' ? 'bg-white/20' : 'bg-indigo-50 text-indigo-600' }}">{{ $stats['ocr'] }}</span>
            </a>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="relative z-10 mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-3 anim-fade-in">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- Notifications List -->
    <div class="relative z-10 space-y-3" id="notifications-list-container">
        @forelse($notifications as $index => $notif)
            @php
                $isRead = !is_null($notif->read_at);
                $data = $notif->data;
                $themeColor = $data['color'] ?? 'blue';
                $icon = $data['icon'] ?? 'bell';
                
                $theme = match($themeColor) {
                    'green'  => ['bg' => 'bg-emerald-500', 'light' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'border' => 'border-emerald-200'],
                    'red'    => ['bg' => 'bg-rose-500', 'light' => 'bg-rose-50', 'text' => 'text-rose-600', 'border' => 'border-rose-200'],
                    'blue'   => ['bg' => 'bg-blue-500', 'light' => 'bg-blue-50', 'text' => 'text-blue-600', 'border' => 'border-blue-200'],
                    'amber'  => ['bg' => 'bg-amber-500', 'light' => 'bg-amber-50', 'text' => 'text-amber-600', 'border' => 'border-amber-200'],
                    default  => ['bg' => 'bg-slate-500', 'light' => 'bg-slate-50', 'text' => 'text-slate-600', 'border' => 'border-slate-200'],
                };
                
                $delayClass = 'delay-'.(min(($index % 10) * 100 + 200, 1000));
            @endphp
            
            <div id="notif-{{ $notif->id }}" onclick="visitUrl('{{ $data['url'] ?? '' }}', '{{ $notif->id }}')" 
                 class="group anim-slide-up {{ $delayClass }} relative bg-white rounded-2xl border {{ $isRead ? 'border-slate-100 opacity-75 hover:opacity-100' : $theme['border'].' shadow-sm hover:shadow-md' }} p-4 sm:p-5 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 overflow-hidden flex gap-4">
                
                {{-- Unread Indicator --}}
                @if(!$isRead)
                    <div class="absolute left-0 top-0 bottom-0 w-1 {{ $theme['bg'] }}"></div>
                @endif
                
                {{-- Icon --}}
                <div class="shrink-0">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $theme['light'] }} {{ $theme['text'] }} transition-transform group-hover:scale-110 duration-300">
                        <i data-lucide="{{ $icon }}" class="w-6 h-6"></i>
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-1 sm:gap-4 mb-1">
                        <h3 class="font-bold text-slate-800 text-sm sm:text-base group-hover:text-blue-600 transition-colors flex items-center gap-2 flex-wrap truncate">
                            @if(!$isRead)
                                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-wider shrink-0">Baru</span>
                            @endif
                            <span class="truncate">{{ $data['title'] ?? 'Notifikasi' }}</span>
                        </h3>
                        <div class="flex items-center gap-1.5 text-xs font-medium text-slate-400 shrink-0">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            <span>{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    
                    <p class="text-slate-600 text-sm leading-relaxed mb-3 line-clamp-2 sm:line-clamp-none">{{ $data['message'] ?? '' }}</p>
                    
                    {{-- Footer: Tag & Actions --}}
                    <div class="flex items-center justify-between gap-3 mt-auto">
                        <div class="min-w-0">
                            @if(isset($data['invoice_number']))
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-50 text-slate-600 border border-slate-200 rounded-lg text-xs font-semibold truncate max-w-full">
                                    <i data-lucide="hash" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                    <span class="truncate">{{ $data['invoice_number'] }}</span>
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if(!$isRead)
                                <form action="{{ route('notifications.read', $notif->id) }}" method="POST" onclick="event.stopPropagation();">
                                    @csrf
                                    <button type="submit" class="p-2 bg-white border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50 rounded-lg transition-colors" title="Tandai Dibaca">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            @endif
                            <button type="button" onclick="event.stopPropagation(); confirmDestroy('{{ $notif->id }}')" class="p-2 bg-white border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-300 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Permanen">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="anim-fade-in delay-200 bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
                <div class="w-20 h-20 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i data-lucide="bell-off" class="w-10 h-10 text-slate-300"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Belum Ada Notifikasi</h3>
                <p class="text-slate-500 text-sm max-w-sm mx-auto">Semua pemberitahuan aktivitas, update OCR, dan status transaksi Anda akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
        <div class="mt-8 pb-8 anim-slide-up delay-700">
            {{ $notifications->withQueryString()->links('components.pagination.premium') }}
        </div>
    @endif
</div>

@push('styles')
<style>
    @keyframes slideUpFade {
        0% { opacity: 0; transform: translateY(15px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideDownFade {
        0% { opacity: 0; transform: translateY(-15px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        0% { opacity: 0; }
        100% { opacity: 1; }
    }
    @keyframes pulseSlow {
        0%, 100% { transform: scale(1); opacity: 0.2; }
        50% { transform: scale(1.05); opacity: 0.4; }
    }
    
    .anim-slide-up { animation: slideUpFade 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }
    .anim-slide-down { animation: slideDownFade 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }
    .anim-fade-in { animation: fadeIn 0.5s ease-out forwards; opacity: 0; }
    .anim-pulse-slow { animation: pulseSlow 8s ease-in-out infinite; }
    
    .delay-100 { animation-delay: 100ms; }
    .delay-200 { animation-delay: 200ms; }
    .delay-300 { animation-delay: 300ms; }
    .delay-400 { animation-delay: 400ms; }
    .delay-500 { animation-delay: 500ms; }
    .delay-700 { animation-delay: 700ms; }

    /* Hide scrollbar for Chrome, Safari and Opera */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    /* Hide scrollbar for IE, Edge and Firefox */
    .scrollbar-hide {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>
@endpush

@push('scripts')
{{-- Biarkan javascript Anda yang lama, karena logic-nya sudah benar --}}
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

    // Echo & Reverb logic
    window.handleRealtimeNotification = function(notification) {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newList = doc.querySelector('#notifications-list-container');
                const oldList = document.querySelector('#notifications-list-container');
                if (newList && oldList) oldList.innerHTML = newList.innerHTML;
                
                const headerClass = '.relative.z-10.flex.flex-col.md\\:flex-row';
                const newHeader = doc.querySelector(headerClass);
                const oldHeader = document.querySelector(headerClass);
                if (newHeader && oldHeader) oldHeader.innerHTML = newHeader.innerHTML;
                
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (typeof updateNotificationBadge === 'function') updateNotificationBadge();
            });
    };
    
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.Echo !== 'undefined') {
            const userId = {{ Auth::id() }};
            window.Echo.private(`App.Models.User.${userId}`)
                .notification((notification) => window.handleRealtimeNotification(notification));
            window.Echo.private(`notifications.${userId}`)
                .listen('.notification.received', (e) => window.handleRealtimeNotification(e));
        }
    });
</script>
@endpush
@endsection