@extends('layouts.app')

@section('page-title', 'Memproses Halaman...')

@section('content')
<div class="fixed inset-0 bg-white z-[60] flex flex-col items-center justify-center">
    <div class="relative w-24 h-24 mb-6">
        {{-- Outer Ring --}}
        <div class="absolute inset-0 border-4 border-indigo-100 rounded-full"></div>
        {{-- Spinning Ring --}}
        <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
        {{-- Inner Icon --}}
        <div class="absolute inset-0 flex items-center justify-center">
            <i data-lucide="package" class="w-10 h-10 text-indigo-600 animate-pulse"></i>
        </div>
    </div>
    
    <div class="text-center stagger-item">
        <h2 class="text-2xl font-black text-slate-800 tracking-tight mb-2">Menyiapkan Form Belanja</h2>
        <p class="text-slate-500 font-medium">Mohon tunggu sebentar, sedang memproses data...</p>
    </div>

    {{-- Progress Bar --}}
    <div class="w-64 h-1.5 bg-slate-100 rounded-full mt-8 overflow-hidden">
        <div id="progress-bar" class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 w-0 transition-all duration-[2000ms] ease-out"></div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const bar = document.getElementById('progress-bar');
        if (bar) {
            // Trigger animation
            setTimeout(() => {
                bar.style.width = '100%';
            }, 100);
        }

        // Redirect after delay
        setTimeout(() => {
            window.location.href = "{{ route('gudang.form') }}";
        }, 2000);
    });
</script>
@endpush
@endsection
