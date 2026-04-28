@extends('layouts.app')

@section('page-title', 'Data Riwayat Transaksi')

@section('content')
    <style>
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .tabs-scroll-container {
            position: relative;
            mask-image: linear-gradient(to right, black 85%, transparent 100%);
            -webkit-mask-image: linear-gradient(to right, black 85%, transparent 100%);
        }

        @media (max-width: 768px) {
            .filter-trigger {
                justify-content: space-between;
            }

            .filter-group {
                width: 100%;
            }
        }
    </style>
    {{-- Main Content Card --}}
    <div class="bg-white shadow-sm border border-gray-100">
        {{-- Header Toolbar --}}
        <div class="p-3 sm:p-4 md:p-5 border-b border-gray-100 bg-white/80 backdrop-blur-sm">
            @include('transactions.partials.index.filter-toolbar')

            {{-- Active Filters Indicator --}}
            <div id="active-filters-bar"
                class="px-3 sm:px-5 py-2 border-b border-gray-50 bg-gray-50/30 flex flex-wrap items-center gap-2 hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mr-1">Filter Aktif:</span>
                <div id="active-filters-chips" class="flex flex-wrap items-center gap-2"></div>
                <button type="button" id="clear-all-filters"
                    class="text-[10px] font-bold text-red-500 hover:text-red-600 ml-auto transition-colors">Hapus
                    Semua</button>
            </div>

            {{-- Search Results Container --}}
            <div id="search-results-container">
                {{-- Status Tabs --}}
                @include('transactions.partials.index.status-tabs')

                {{-- Table View --}}
                @include('transactions.partials.index.desktop-table')

                {{-- Mobile/Tablet Card View --}}
                @include('transactions.partials.index.mobile-list')

                {{-- Footer / Pagination --}}
                @include('transactions.partials.index.pagination')
            </div>{{-- end #search-results-container --}}
        </div>


        @if(auth()->user()->role !== 'teknisi')
            {{-- ══════════════════════════════════════════════════ --}}
            {{-- EXPORT MODAL: Filter Laporan Bulanan --}}
            {{-- ══════════════════════════════════════════════════ --}}
            @include('transactions.partials.modals.export-excel-modal')
        @endif


        @push('modals')
            {{-- VIEW DETAIL MODAL --}}
            @include('transactions.partials.modals.view-detail-modal')

            {{-- IMAGE/PDF VIEWER MODAL (Fullscreen Zoom) --}}
            @include('transactions.partials.modals.image-pdf-view-modal')

            {{-- REJECT MODAL --}}
            @include('transactions.partials.modals.reject-modal')

            {{-- OVERRIDE MODAL --}}
            @include('transactions.partials.modals.override-modal')

            {{-- FORCE APPROVE MODAL --}}
            @include('transactions.partials.modals.force-approve-modal')

            {{-- PAYMENT UPLOAD MODAL --}}
            @include('transactions.partials.modals.payment-upload-modal')

            {{-- BRANCH DEBT SETTLEMENT MODAL --}}
            @include('transactions.partials.modals.branch-debt-modal')

            {{-- DELETE CONFIRMATION MODAL --}}
            <x-confirm-modal id="deleteTransactionModal" />
        @endpush

@endsection

@section('styles')
        <style>
            .ai-status-badge {
                transition: all 0.2s ease-in-out;
            }

            .ai-status-badge:hover {
                transform: scale(1.05);
                z-index: 10;
            }

            @keyframes subtle-pulse {

                0%,
                100% {
                    opacity: 1;
                }

                50% {
                    opacity: 0.7;
                }
            }

            .ai-status-badge.animate-pulse {
                animation: subtle-pulse 2s ease-in-out infinite;
            }

            #toast-container .flex.items-center.gap-3 {
                border-left: 4px solid transparent;
            }

            #toast-container .bg-emerald-600 {
                border-left-color: #34d399;
            }

            #toast-container .bg-red-600 {
                border-left-color: #f87171;
            }

            #toast-container .bg-blue-600 {
                border-left-color: #60a5fa;
            }

            /* ── Branch Tag Truncation Tooltip ── */
            .branch-more-wrap {
                position: relative;
                display: inline-flex;
            }

            .branch-more-wrap .branch-tooltip {
                visibility: hidden;
                opacity: 0;
                position: absolute;
                bottom: calc(100% + 8px);
                left: 50%;
                transform: translateX(-50%);
                background: #1e293b;
                color: #f8fafc;
                font-size: 10px;
                font-weight: 600;
                line-height: 1.4;
                padding: 8px 12px;
                border-radius: 10px;
                white-space: nowrap;
                z-index: 50;
                pointer-events: none;
                transition: opacity 0.2s ease, visibility 0.2s ease;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .branch-more-wrap .branch-tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                transform: translateX(-50%);
                border: 5px solid transparent;
                border-top-color: #1e293b;
            }

            .branch-more-wrap:hover .branch-tooltip {
                visibility: visible;
                opacity: 1;
            }
        </style>
@endsection


@push('scripts')
    <script>
        window.AppConfig = {
            csrfToken: '{{ csrf_token() }}',
            userRole: '{{ Auth::user()->role }}',
            userId: '{{ Auth::id() }}',
            userName: '{{ Auth::user()->name }}',
            userIsAdmin: {{ Auth::user()->isAdmin() ? 'true' : 'false' }},
            userCanManage: {{ Auth::user()->canManageStatus() ? 'true' : 'false' }},
            userIsOwner: {{ Auth::user()->isOwner() ? 'true' : 'false' }}
        };
    </script>
@endpush
