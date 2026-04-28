@extends('layouts.app')
@section('page-title', 'Form Pengajuan Beli')
@section('content')
    {{-- Form Container --}}
    <div class="bg-white shadow-sm border border-slate-100 p-3 pt-6 md:p-8 lg:p-10">
        {{-- <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-3 pt-6 md:p-8 lg:p-10"> --}}

            {{-- Header --}}
            @include('transactions.partials.forms.pengajuan.header')

            <form method="POST" action="{{ route('pengajuan.store') }}" id="pengajuan-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" value="pengajuan">

                {{-- Container untuk input tersembunyi distribusi (PENTING) --}}
                <div id="distribution-hidden-inputs"></div>

                {{-- 1. FOTO REFERENSI --}}
                @include('transactions.partials.forms.shared.photo-section')
                {{-- ══════════════════════════════════ --}}
                {{-- 2. DAFTAR BARANG (DYNAMIC) --}}
                {{-- ══════════════════════════════════ --}}

                @include('transactions.partials.forms.pengajuan.item-repeater')

                {{-- ══════════════════════════════════ --}}
                {{-- 3. DISTRIBUSI CABANG (FULL WIDTH CARD) --}}
                {{-- ══════════════════════════════════ --}}

                @include('transactions.partials.forms.shared.branch-distribution')

                {{-- Divider --}}
                <div class="relative flex justify-center items-center mb-8">
                    <div class="w-full h-px bg-slate-100 absolute"></div>
                    <span
                        class="bg-white px-4 relative z-10 text-[9px] md:text-[10px] font-bold text-slate-300 uppercase tracking-[0.2em]">Summary
                        Billing</span>
                </div>

                {{-- ══════════════════════════════════ --}}
                {{-- SUMMARY BILLING (FULL WIDTH BLACK CARD) --}}
                {{-- ══════════════════════════════════ --}}

                @include('transactions.partials.forms.shared.summary-billing')

                @if($errors->any())
                    <div class="mt-4 bg-red-50 border border-red-100 rounded-xl p-4 hidden" id="fallback-error-msg">
                        <div class="flex items-start gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 mt-0.5 shrink-0"></i>
                            <div class="text-xs text-red-600 font-medium">
                                @foreach($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>

        {{-- Toast Container --}}
        <div id="toast-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"></div>

        @push('modals')
            @include('transactions.partials.forms.shared.image-viewer-modal')
        @endpush

        {{-- ITEM TEMPLATE FOR JS --}}
        <template id="item-template">
            @include('transactions.partials.forms.pengajuan.item-template')
        </template>

        <script>
            lucide.createIcons();
        </script>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Show validation errors via toast if they exist
                    @if($errors->any())
                        @foreach($errors->all() as $error)
                            setTimeout(() => {
                                if (window.showToast) window.showToast("{{ $error }}", 'error');
                            }, {{ $loop->index * 300 }}); // stagger multiple toasts
                        @endforeach
                    @endif
                });
            </script>
        @endpush
@endsection