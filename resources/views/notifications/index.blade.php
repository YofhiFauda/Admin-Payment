@extends('layouts.app')

@section('page-title', 'Notifikasi')

@section('content')
<div class="px-4 py-8 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Notifikasi</h1>
            <p class="mt-2 text-slate-500 font-medium">Pemberitahuan terkait proses OCR dan status transaksi.</p>
        </div>
        
        @if($stats['unread'] > 0)
        <form action="{{ route('notifications.readAll') }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="{{ request('type') }}">
            <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-xl font-bold text-sm hover:bg-blue-100 transition-colors">
                <i data-lucide="check-check" class="w-4 h-4"></i> Tandai Semua Dibaca
            </button>
        </form>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-6 p-2 flex overflow-x-auto scrollbar-hide">
        @php $currentType = request('type', 'all'); $currentRead = request('read'); @endphp
        
        <a href="{{ route('notifications.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap {{ $currentType === 'all' && !$currentRead ? 'bg-slate-800 text-white' : 'text-slate-500 hover:bg-slate-50' }}">
            Semua ({{ $stats['total'] }})
        </a>
        <a href="{{ route('notifications.index', ['read' => 'unread']) }}" class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap {{ $currentRead === 'unread' ? 'bg-slate-800 text-white' : 'text-slate-500 hover:bg-slate-50' }}">
            Belum Dibaca ({{ $stats['unread'] }})
        </a>
        <a href="{{ route('notifications.index', ['type' => 'ocr']) }}" class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap {{ $currentType === 'ocr' ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-indigo-50' }}">
            OCR ({{ $stats['ocr'] }})
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-bold flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- List -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        @forelse($notifications as $notif)
            @php
                $isRead = !is_null($notif->read_at);
                $data = $notif->data;
                $color = $data['color'] ?? 'blue';
                $icon = $data['icon'] ?? 'bell';
                
                $colorClasses = match($color) {
                    'green'  => 'bg-emerald-100 text-emerald-600',
                    'red'    => 'bg-rose-100 text-rose-600',
                    'indigo' => 'bg-indigo-100 text-indigo-600',
                    default  => 'bg-blue-100 text-blue-600',
                };
            @endphp
            
            <div class="group flex flex-col sm:flex-row gap-4 p-5 md:p-6 border-b border-slate-50 hover:bg-slate-50/50 transition-colors {{ $isRead ? 'opacity-70' : '' }}">
                
                <!-- Icon -->
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center {{ $colorClasses }}">
                        <i data-lucide="{{ $icon }}" class="w-6 h-6"></i>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-grow">
                    <div class="flex justify-between items-start gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-bold text-slate-800 text-base">{{ $data['title'] ?? 'Notifikasi' }}</h3>
                                @if(!$isRead)
                                    <span class="px-2 py-0.5 rounded border border-blue-200 bg-blue-50 text-blue-600 text-[9px] font-black uppercase tracking-widest">Baru</span>
                                @endif
                            </div>
                            <p class="text-slate-600 text-sm leading-relaxed mb-2">{{ $data['message'] ?? '' }}</p>
                            
                            <div class="flex items-center gap-4 text-xs font-bold text-slate-400">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                    {{ $notif->created_at->diffForHumans() }}
                                </span>
                                
                                @if(isset($data['invoice_number']))
                                    <span class="flex items-center gap-1.5 uppercase tracking-wider text-slate-500">
                                        <i data-lucide="hash" class="w-3.5 h-3.5"></i>
                                        {{ $data['invoice_number'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                            @if(isset($data['url']))
                                <a href="{{ $data['url'] }}" class="p-2 bg-white border border-slate-200 text-slate-600 hover:text-blue-600 hover:border-blue-200 rounded-xl shadow-sm transition-all" title="Lihat Detail">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if(!$isRead)
                                <form action="{{ route('notifications.read', $notif->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="p-2 bg-white border border-slate-200 text-slate-600 hover:text-emerald-600 hover:border-emerald-200 rounded-xl shadow-sm transition-all" title="Tandai Dibaca">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('notifications.destroy', $notif->id) }}" method="POST" onsubmit="return confirm('Hapus notifikasi ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 bg-white border border-slate-200 text-slate-600 hover:text-rose-600 hover:border-rose-200 rounded-xl shadow-sm transition-all" title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-16 text-center">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="bell-off" class="w-10 h-10 text-slate-300"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">Pemberitahuan Kosong</h3>
                <p class="text-slate-500 text-sm">Anda belum memiliki notifikasi saat ini.</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-6 flex justify-end">
            {{ $notifications->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection