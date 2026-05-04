@extends('layouts.app')

@section('page-title', 'Kelola Pengguna')

@section('content')
<div class="bg-white shadow-sm border border-slate-200 overflow-hidden">
    {{-- Card Header & Toolbar --}}
    <div class="p-4 sm:p-6 border-b border-slate-100 bg-slate-50/30">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            {{-- Left: Stats & Title --}}
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-linear-to-r from-sky-600 to-sky-500 flex items-center justify-center shadow-lg shadow-blue-500/20 shrink-0">
                    <i data-lucide="users" class="w-6 h-6 md:w-7 md:h-7 text-white"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-lg md:text-xl font-black text-slate-900 leading-tight truncate">Daftar Pengguna</h1>
                    <p class="text-[10px] md:text-xs text-slate-500 font-bold uppercase tracking-wider mt-0.5">Total {{ $users->total() }} pengguna terdaftar</p>
                </div>
            </div>

            {{-- Right: Actions --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                {{-- Search & Filter Form --}}
                <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1">
                    {{-- Search --}}
                    <div class="relative flex-1 sm:min-w-[240px] xl:min-w-[320px]">
                        <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 outline-none transition-all">
                    </div>

                    {{-- Role Filter --}}
                    <div class="relative shrink-0">
                        <select name="role" onchange="this.form.submit()"
                                class="w-full sm:w-auto pl-4 pr-10 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">Semua Role</option>
                            <option value="teknisi" {{ request('role') === 'teknisi' ? 'selected' : '' }}>Teknisi</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="atasan" {{ request('role') === 'atasan' ? 'selected' : '' }}>Atasan</option>
                            <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                    </div>

                    <button type="submit"
                            class="hidden sm:block px-5 py-2.5 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-colors shadow-sm active:scale-[0.98]">
                        Cari
                    </button>
                </form>

                <div class="hidden lg:block w-px h-8 bg-slate-200 mx-1"></div>

                <a href="{{ route('users.create') }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-linear-to-r from-sky-600 to-sky-500 text-white rounded-xl font-bold text-sm shadow-lg shadow-sky-600/20 hover:shadow-xl hover:shadow-sky-500/30 transition-all active:scale-[0.98]">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span class="sm:hidden xl:inline">Tambah Pengguna</span>
                    <span class="hidden sm:inline xl:hidden">Tambah</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="overflow-hidden">
        {{-- Desktop Table --}}
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] w-12">#</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Pengguna</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] hidden lg:table-cell">Email</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Akses / Role</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] hidden xl:table-cell">Terdaftar</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($users as $index => $user)
                    <tr class="hover:bg-slate-50/50 transition-colors group" id="user-row-{{ $user->id }}">
                        <td class="px-6 py-4 text-sm text-slate-400 font-bold tracking-tight">{{ $users->firstItem() + $index }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-gradient-to-br from-blue-500/10 to-blue-500/10 border border-blue-100 flex items-center justify-center text-blue-600 text-xs md:text-sm font-black flex-shrink-0 group-hover:scale-110 transition-transform">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-sm font-bold text-slate-800 truncate">{{ $user->name }}</span>
                                    <span class="block text-[10px] font-bold text-slate-400 lg:hidden truncate">{{ $user->email }}</span>
                                    @if($user->id === auth()->id())
                                        <span class="text-[9px] font-black text-sky-500 uppercase tracking-widest bg-sky-50 px-1.5 py-0.5 rounded-md mt-0.5 inline-block">Anda</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 font-bold hidden lg:table-cell">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            @php
                                $roleColors = [
                                    'owner'   => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'atasan'  => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'admin'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'teknisi' => 'bg-slate-50 text-slate-700 border-slate-200',
                                ];
                            @endphp
                            <span class="inline-flex px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border {{ $roleColors[$user->role] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 font-bold tracking-tight hidden xl:table-cell">{{ $user->created_at->translatedFormat('d F Y') }}</td>
                        <td class="px-6 py-4">
                            @php
                                $canManage = Auth::user()->isOwner() || $user->role === 'teknisi';
                            @endphp
                            <div class="flex items-center justify-end gap-1">
                                @if($user->role === 'teknisi')
                                <button type="button" onclick="openBankAccountsModal({{ $user->id }})" title="Kelola Rekening"
                                    class="p-2 rounded-xl text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all hover:scale-110">
                                    <i data-lucide="credit-card" class="w-4.5 h-4.5"></i>
                                </button>
                                @endif
                                @if($canManage)
                                <a href="{{ route('users.edit', $user->id) }}" title="Edit User"
                                   class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-indigo-600 transition-all hover:scale-110">
                                    <i data-lucide="pencil" class="w-4.5 h-4.5"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                <button type="button" title="Hapus User"
                                        onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                        class="p-2 rounded-xl text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all hover:scale-110">
                                    <i data-lucide="trash-2" class="w-4.5 h-4.5"></i>
                                </button>
                                @endif
                                @else
                                <span class="px-4 text-xs text-slate-300 font-bold italic tracking-widest uppercase">Locked</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center gap-4 text-slate-300">
                                <div class="w-20 h-20 rounded-3xl bg-slate-50 flex items-center justify-center border-2 border-dashed border-slate-200">
                                    <i data-lucide="users-2" class="w-10 h-10"></i>
                                </div>
                                <div>
                                    <p class="text-base font-black text-slate-900">Belum ada pengguna</p>
                                    <p class="text-sm font-bold text-slate-400">Silakan tambahkan pengguna baru untuk mulai.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile & Tablet Card List --}}
        <div class="lg:hidden grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:gap-4 p-4">
            @forelse($users as $user)
            <div class="py-5 md:p-6 md:bg-slate-50/50 md:rounded-2xl md:border md:border-slate-100 flex flex-col gap-4 relative overflow-hidden group" id="user-card-{{ $user->id }}">
                {{-- Decorative background --}}
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-slate-50 rounded-full opacity-0 group-active:opacity-100 transition-opacity"></div>
                
                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-sky-600/10 to-sky-600/5 border border-sky-100 flex items-center justify-center text-sky-600 text-lg font-black shrink-0 shadow-sm">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-black text-slate-800 truncate">{{ $user->name }}</p>
                                @if($user->id === auth()->id())
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 shadow-[0_0_8px_rgba(14,165,233,0.5)]"></span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-400 font-bold truncate mt-0.5">{{ $user->email }}</p>
                        </div>
                    </div>
                    @php
                        $roleColors = [
                            'owner'   => 'bg-amber-50 text-amber-700 border-amber-200',
                            'atasan'  => 'bg-blue-50 text-blue-700 border-blue-200',
                            'admin'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'teknisi' => 'bg-slate-50 text-slate-700 border-slate-200',
                        ];
                    @endphp
                    <span class="inline-flex px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border {{ $roleColors[$user->role] ?? 'bg-slate-50 text-slate-600 border-slate-200' }} shrink-0">
                        {{ $user->role }}
                    </span>
                </div>

                <div class="relative flex items-center justify-between mt-auto pt-4 border-t border-slate-100/50">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-300"></i>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Terdaftar</span>
                        </div>
                        <span class="text-[11px] text-slate-600 font-black ml-5">{{ $user->created_at->translatedFormat('d M Y') }}</span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        @if($user->role === 'teknisi')
                        <button type="button" onclick="openBankAccountsModal({{ $user->id }})"
                                class="w-10 h-10 flex items-center justify-center rounded-xl bg-sky-50 text-sky-600 active:scale-90 transition-all border border-sky-100/50">
                            <i data-lucide="credit-card" class="w-4.5 h-4.5"></i>
                        </button>
                        @endif
                        
                        @if(Auth::user()->isOwner() || $user->role === 'teknisi')
                            <a href="{{ route('users.edit', $user->id) }}"
                               class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-600 active:scale-90 transition-all border border-slate-200/50">
                                <i data-lucide="pencil" class="w-4.5 h-4.5"></i>
                            </a>
                            @if($user->id !== auth()->id())
                                <button type="button"
                                        onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-50 text-red-500 active:scale-90 transition-all border border-red-100/50">
                                    <i data-lucide="trash-2" class="w-4.5 h-4.5"></i>
                                </button>
                            @endif
                        @else
                            <div class="px-3 py-1.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Locked</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="p-12 text-center col-span-full">
                <p class="text-sm font-bold text-slate-400 italic">Belum ada pengguna terdaftar</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Card Footer: Pagination --}}
    @if($users->hasPages())
    <div class="p-4 sm:p-6 border-t border-slate-100 bg-slate-50/20">
        <div class="flex justify-center">
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>


@endsection

@push('scripts')
<script>
    function confirmDelete(userId, userName) {
        openConfirmModal('globalConfirmModal', {
            message: `Anda yakin ingin menghapus <strong class="text-slate-800">${userName}</strong>? Tindakan ini tidak dapat dibatalkan.`,
            action: `/users/${userId}`,
            method: 'DELETE',
            onConfirm: async () => {
                try {
                    const response = await fetch(`/users/${userId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        showToast(result.message, 'success');
                        // Instant remove from UI
                        const row = document.getElementById(`user-row-${userId}`);
                        const card = document.getElementById(`user-card-${userId}`);
                        if (row) {
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(-20px)';
                            row.style.transition = 'all 0.3s ease';
                            setTimeout(() => row.remove(), 300);
                        }
                        if (card) {
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.95)';
                            card.style.transition = 'all 0.3s ease';
                            setTimeout(() => card.remove(), 300);
                        }
                    } else {
                        throw new Error(result.message || 'Gagal menghapus pengguna');
                    }
                } catch (err) {
                    showToast(err.message, 'error');
                    throw err; // Re-throw to keep modal open if we want, or just let it close
                }
            }
        });
    }

    function closeDeleteModal() {
        closeConfirmModal('globalConfirmModal');
    }
</script>
@endpush
