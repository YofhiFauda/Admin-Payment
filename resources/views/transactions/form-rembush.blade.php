@extends('layouts.app')

@section('page-title', 'Form Reimbursement')

@section('content')
    <div class="max-w-8xl mx-auto">
        <div class="bg-white shadow-sm border border-slate-100 p-3 pt-6 md:p-8 lg:p-10">
            
            {{-- Header --}}
            @include('transactions.partials.forms.rembush.header')

            <form method="POST" action="{{ route('rembush.store') }}" id="transaction-form" enctype="multipart/form-data">
                @csrf
                
                {{-- Alert Errors --}}
                @if ($errors->any())
                    <div class="mb-8 md:mb-10 bg-red-50 border border-red-200 text-red-600 rounded-xl p-4 md:p-5 text-xs md:text-sm">
                        <strong class="font-bold">Terjadi Kesalahan:</strong>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <input type="hidden" name="type" value="rembush">
                @if(isset($uploadId))
                    <input type="hidden" id="upload-id" value="{{ $uploadId }}">
                @endif
                <input type="hidden" name="amount" id="form-total-amount" value="{{ old('amount', 0) }}">
                
                {{-- 0. MANAGEMENT: INPUT FOR TECHNICIAN --}}
                @include('transactions.partials.forms.rembush.technician-section')
                
                {{-- 1. FOTO REFERENSI (Shared) --}}
                @include('transactions.partials.forms.shared.photo-section')

                {{-- 2. MAIN INFO FIELDS --}}
                @include('transactions.partials.forms.rembush.main-info')

                {{-- 3. DAFTAR BARANG --}}
                @include('transactions.partials.forms.rembush.item-repeater')

                {{-- 4. PEMBAGIAN CABANG (Shared) --}}
                @include('transactions.partials.forms.shared.branch-distribution')

                {{-- Summary Billing (Shared) --}}
                <div class="relative flex justify-center items-center mb-8">
                    <div class="w-full h-px bg-slate-100 absolute"></div>
                    <span class="bg-white px-4 relative z-10 text-[9px] md:text-[10px] font-bold text-slate-300 uppercase tracking-[0.2em]">Summary Billing</span>
                </div>
                @include('transactions.partials.forms.shared.summary-billing', ['submitButtonText' => 'Kirim Pengajuan Rembush'])

                {{-- Branch hidden inputs --}}
                <div id="distribution-hidden-inputs"></div>
            </form>
        </div>
    </div>

    {{-- Templates --}}
    @include('transactions.partials.forms.rembush.item-template')

    @push('modals')
        @include('transactions.partials.forms.shared.image-viewer-modal')
    @endpush

@endsection

@push('scripts')
    <script>
        window._aiData = @json($aiData ?? []);
    </script>
@endpush
