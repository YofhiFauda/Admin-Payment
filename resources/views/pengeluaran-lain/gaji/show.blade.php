@extends('layouts.app')

@section('page-title', 'Detail Gaji — ' . $salary->invoice_number)

@section('content')
<div class="p-4 md:p-6">
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('pengeluaran-lain.gaji.index') }}"
                    class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-all shadow-sm">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Detail Gaji</h1>
                    <p class="text-sm font-mono text-slate-400">{{ $salary->invoice_number }}</p>
                </div>
            </div>
            {{-- Status Badge --}}
            @php
                $colorMap = ['draft' => 'gray', 'approved' => 'blue', 'paid' => 'green'];
                $c = $colorMap[$salary->status] ?? 'gray';
            @endphp
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-black bg-{{ $c }}-100 text-{{ $c }}-700">
                <span class="w-2 h-2 rounded-full bg-{{ $c }}-500 inline-block"></span>
                {{ $salary->status_label }}
            </span>
        </div>

        {{-- Flash --}}
        @if(session('notification'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3.5 rounded-xl text-sm font-semibold flex items-center gap-3">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
            {{ session('notification') }}
        </div>
        @endif

        {{-- Approval Timeline --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4">Alur Persetujuan</h2>
            <div class="flex items-center gap-0">
                {{-- Step 1: Draft --}}
                <div class="flex flex-col items-center">
                    <div class="w-9 h-9 rounded-full {{ in_array($salary->status, ['draft','approved','paid']) ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center font-black text-sm">1</div>
                    <p class="text-xs font-bold text-slate-500 mt-1.5">Draft</p>
                </div>
                <div class="flex-1 h-0.5 {{ in_array($salary->status, ['approved','paid']) ? 'bg-blue-500' : 'bg-slate-200' }} mx-2"></div>
                {{-- Step 2: Approved --}}
                <div class="flex flex-col items-center">
                    <div class="w-9 h-9 rounded-full {{ in_array($salary->status, ['approved','paid']) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center font-black text-sm">2</div>
                    <p class="text-xs font-bold text-blue-500 mt-1.5">Disetujui</p>
                </div>
                <div class="flex-1 h-0.5 {{ $salary->status === 'paid' ? 'bg-green-500' : 'bg-slate-200' }} mx-2"></div>
                {{-- Step 3: Paid --}}
                <div class="flex flex-col items-center">
                    <div class="w-9 h-9 rounded-full {{ $salary->status === 'paid' ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center font-black text-sm">3</div>
                    <p class="text-xs font-bold text-green-500 mt-1.5">Dibayar</p>
                </div>
            </div>
        </div>

        {{-- Info Karyawan --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-3">Informasi Karyawan</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Karyawan</p>
                    <p class="font-black text-slate-800 mt-1">{{ $salary->employee->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Periode</p>
                    <p class="font-black text-slate-800 mt-1">{{ $salary->periode }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Diinput oleh</p>
                    <p class="font-semibold text-slate-700 mt-1">{{ $salary->submitter->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tanggal Input</p>
                    <p class="font-semibold text-slate-700 mt-1">{{ $salary->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Komponen Gaji --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-green-500"></i> Komponen Gaji
            </h2>
            @php
            $komponents = [
                'Gaji Pokok'  => $salary->gaji_pokok,
                'Bonus 1'     => $salary->bonus_1,
                'Bonus 2'     => $salary->bonus_2,
                'Tunjangan'   => $salary->tunjangan,
                'Lembur'      => $salary->lembur,
                'Bensin'      => $salary->bensin,
                'Lebih Hari'  => $salary->lebih_hari,
            ];
            $totalKomp = array_sum($komponents);
            @endphp
            <div class="space-y-2">
                @foreach($komponents as $label => $value)
                @if($value > 0)
                <div class="flex items-center justify-between py-2 border-b border-slate-50">
                    <span class="text-sm font-semibold text-slate-600">{{ $label }}</span>
                    <span class="text-sm font-bold text-slate-800">Rp {{ number_format($value, 0, ',', '.') }}</span>
                </div>
                @endif
                @endforeach
                <div class="flex items-center justify-between py-2 bg-green-50 rounded-lg px-3 mt-2">
                    <span class="text-sm font-black text-green-700">Total Komponen</span>
                    <span class="text-sm font-black text-green-700">Rp {{ number_format($totalKomp, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Potongan --}}
        @if($salary->potongan_absen > 0 || $salary->potongan_bon > 0)
        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm p-6">
            <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-rose-100 pb-3 mb-4 flex items-center gap-2">
                <i data-lucide="minus-circle" class="w-4 h-4 text-rose-500"></i> Potongan
            </h2>
            <div class="space-y-2">
                @if($salary->potongan_absen > 0)
                <div class="flex items-center justify-between py-2 border-b border-slate-50">
                    <span class="text-sm font-semibold text-slate-600">Potongan Absensi <span class="text-xs text-slate-400">(absen, cuti, mangkir, telat)</span></span>
                    <span class="text-sm font-bold text-rose-600">− Rp {{ number_format($salary->potongan_absen, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($salary->potongan_bon > 0)
                <div class="flex items-center justify-between py-2 border-b border-slate-50">
                    <span class="text-sm font-semibold text-slate-600">Potongan Bon/Angsuran</span>
                    <span class="text-sm font-bold text-rose-600">− Rp {{ number_format($salary->potongan_bon, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Total --}}
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-sm font-bold text-green-100 mb-1">Total Gaji Bersih</p>
            <p class="text-4xl font-black">{{ $salary->formatted_total }}</p>
        </div>

        {{-- Catatan --}}
        @if($salary->catatan_atasan)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
            <p class="text-xs font-black text-amber-600 uppercase tracking-wider mb-2">Catatan Atasan</p>
            <p class="text-sm font-semibold text-amber-800">{{ $salary->catatan_atasan }}</p>
        </div>
        @endif

        {{-- Approval Info --}}
        @if($salary->approver)
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs font-black text-blue-400 uppercase tracking-wider">Disetujui oleh</p>
                <p class="font-black text-blue-800 mt-1">{{ $salary->approver->name }}</p>
            </div>
            <div>
                <p class="text-xs font-black text-blue-400 uppercase tracking-wider">Tanggal Persetujuan</p>
                <p class="font-bold text-blue-700 mt-1">{{ $salary->approved_at?->format('d M Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($salary->payer)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-5 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs font-black text-green-400 uppercase tracking-wider">Dibayar oleh</p>
                <p class="font-black text-green-800 mt-1">{{ $salary->payer->name }}</p>
            </div>
            <div>
                <p class="text-xs font-black text-green-400 uppercase tracking-wider">Tanggal Bayar</p>
                <p class="font-bold text-green-700 mt-1">{{ $salary->paid_at?->format('d M Y H:i') }}</p>
            </div>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex flex-wrap gap-3 pb-4">
            {{-- Edit (hanya jika draft) --}}
            @if($salary->isEditable())
            <a href="{{ route('pengeluaran-lain.gaji.edit', $salary->id) }}"
                class="flex items-center gap-2 px-5 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-sm hover:bg-slate-50 transition-all shadow-sm">
                <i data-lucide="edit-2" class="w-4 h-4"></i> Edit
            </a>
            @endif

            {{-- Approve (atasan/owner, hanya jika draft) --}}
            @if($salary->isDraft() && in_array(Auth::user()->role, ['atasan', 'owner']))
            <button type="button"
                onclick="confirmApproveSalary()"
                class="flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20">
                <i data-lucide="check-circle" class="w-4 h-4"></i> Setujui Gaji
            </button>
            @endif

            {{-- Pay (admin/atasan/owner, hanya jika approved) --}}
            @if($salary->isApproved())
            <button type="button"
                onclick="confirmPaySalary()"
                class="flex items-center gap-2 px-5 py-3 rounded-xl bg-green-600 text-white font-bold text-sm hover:bg-green-700 transition-all shadow-lg shadow-green-600/20">
                <i data-lucide="banknote" class="w-4 h-4"></i> Tandai Sudah Dibayar
            </button>
            @endif

            {{-- Delete (hanya jika draft) --}}
            @if($salary->isEditable())
            <button type="button"
                onclick="confirmDeleteSalary()"
                class="flex items-center gap-2 px-5 py-3 rounded-xl bg-white border border-rose-200 text-rose-600 font-bold text-sm hover:bg-rose-50 transition-all shadow-sm">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
            </button>
            @endif
        </div>
    </div>
</div>

<script>
    function confirmApproveSalary() {
        openConfirmModal('globalConfirmModal', {
            title: 'Setujui Gaji?',
            message: 'Apakah Anda yakin ingin menyetujui gaji <strong class="text-slate-800">{{ $salary->employee->name ?? "" }}</strong> periode <strong class="text-slate-800">{{ $salary->periode }}</strong>?',
            action: "{{ route('pengeluaran-lain.gaji.approve', $salary->id) }}",
            method: 'POST',
            submitText: 'Ya, Setujui',
            submitColor: 'bg-blue-600 hover:bg-blue-700',
            icon: 'check-circle',
            iconColor: 'text-blue-600',
            iconBg: 'bg-blue-50',
            onConfirm: async () => {
                const response = await fetch("{{ route('pengeluaran-lain.gaji.approve', $salary->id) }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message || 'Gaji disetujui', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Gagal menyetujui gaji', 'error');
                }
            }
        });
    }

    function confirmPaySalary() {
        openConfirmModal('globalConfirmModal', {
            title: 'Tandai Sudah Bayar?',
            message: 'Apakah Anda yakin ingin menandai gaji ini sebagai <strong>Sudah Dibayar</strong>? Notifikasi akan dikirim ke karyawan via Telegram.',
            action: "{{ route('pengeluaran-lain.gaji.pay', $salary->id) }}",
            method: 'POST',
            submitText: 'Ya, Sudah Bayar',
            submitColor: 'bg-green-600 hover:bg-green-700',
            icon: 'banknote',
            iconColor: 'text-green-600',
            iconBg: 'bg-green-50',
            onConfirm: async () => {
                const response = await fetch("{{ route('pengeluaran-lain.gaji.pay', $salary->id) }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message || 'Gaji dibayar', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Gagal memproses pembayaran', 'error');
                }
            }
        });
    }

    function confirmDeleteSalary() {
        openConfirmModal('globalConfirmModal', {
            title: 'Hapus Data Gaji?',
            message: 'Apakah Anda yakin ingin menghapus data gaji ini secara permanen? Tindakan ini tidak dapat dibatalkan.',
            action: "{{ route('pengeluaran-lain.gaji.destroy', $salary->id) }}",
            method: 'DELETE',
            submitText: 'Ya, Hapus',
            submitColor: 'bg-red-500 hover:bg-red-600',
            icon: 'trash-2',
            iconColor: 'text-red-500',
            iconBg: 'bg-red-50',
            onConfirm: async () => {
                const response = await fetch("{{ route('pengeluaran-lain.gaji.destroy', $salary->id) }}", {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message || 'Gaji dihapus', 'success');
                    setTimeout(() => location.href = "{{ route('pengeluaran-lain.gaji.index') }}", 1000);
                } else {
                    showToast(result.message || 'Gagal menghapus gaji', 'error');
                }
            }
        });
    }
</script>
@endsection
