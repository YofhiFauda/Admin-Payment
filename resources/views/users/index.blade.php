@extends('layouts.app')

@section('page-title', 'Kelola Pengguna')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    {{-- Card Header & Toolbar --}}
    <div class="p-4 sm:p-6 border-b border-slate-100 bg-slate-50/30">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Left: Stats & Title --}}
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i data-lucide="users" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-black text-slate-900 leading-tight">Daftar Pengguna</h1>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-0.5">Total {{ $users->total() }} pengguna terdaftar</p>
                </div>
            </div>

            {{-- Right: Actions --}}
            <div class="flex flex-col sm:flex-row items-center gap-3">
                {{-- Search & Filter Form --}}
                <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                    {{-- Search --}}
                    <div class="relative w-full sm:w-64">
                        <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
                    </div>

                    {{-- Role Filter --}}
                    <div class="relative w-full sm:w-auto">
                        <select name="role" onchange="this.form.submit()"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all appearance-none pr-10">
                            <option value="">Semua Role</option>
                            <option value="teknisi" {{ request('role') === 'teknisi' ? 'selected' : '' }}>Teknisi</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="atasan" {{ request('role') === 'atasan' ? 'selected' : '' }}>Atasan</option>
                            <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                    </div>

                    <button type="submit"
                            class="w-full sm:w-auto px-5 py-2.5 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-colors shadow-sm">
                        Cari
                    </button>
                </form>

                <div class="w-px h-8 bg-slate-200 hidden sm:block mx-1"></div>

                <a href="{{ route('users.create') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 transition-all active:scale-[0.98]">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span class="sm:hidden lg:inline">Tambah Pengguna</span>
                    <span class="hidden sm:inline lg:hidden">Tambah</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="overflow-hidden">
        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest w-12">#</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Pengguna</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Email</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Akses / Role</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Terdaftar</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($users as $index => $user)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4 text-sm text-slate-400 font-bold tracking-tight">{{ $users->firstItem() + $index }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/10 to-purple-500/10 border border-indigo-100 flex items-center justify-center text-indigo-600 text-sm font-black flex-shrink-0 group-hover:scale-110 transition-transform">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <span class="block text-sm font-bold text-slate-800">{{ $user->name }}</span>
                                    @if($user->id === auth()->id())
                                        <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest bg-indigo-50 px-1.5 py-0.5 rounded-md mt-0.5 inline-block">Anda</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 font-bold">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            @php
                                $roleColors = [
                                    'owner'   => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'atasan'  => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'admin'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'teknisi' => 'bg-slate-50 text-slate-700 border-slate-200',
                                ];
                            @endphp
                            <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border {{ $roleColors[$user->role] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 font-bold tracking-tight">{{ $user->created_at->format('d M Y') }}</td>
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

        {{-- Mobile Card List --}}
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($users as $user)
            <div class="p-4 active:bg-slate-50 transition-colors">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-purple-500/10 border border-indigo-100 flex items-center justify-center text-indigo-600 text-base font-black flex-shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-black text-slate-800 truncate">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500 font-bold truncate">{{ $user->email }}</p>
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
                    <span class="inline-flex px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-widest border {{ $roleColors[$user->role] ?? 'bg-slate-50 text-slate-600 border-slate-200' }} flex-shrink-0">
                        {{ $user->role }}
                    </span>
                </div>
                <div class="flex items-center justify-between mt-4">
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span class="text-[10px] text-slate-500 font-black tracking-tight">{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($user->role === 'teknisi')
                        <button type="button" onclick="openBankAccountsModal({{ $user->id }})"
                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 active:scale-90 transition-transform">
                            <i data-lucide="credit-card" class="w-4 h-4"></i>
                        </button>
                        @endif
                        @if(Auth::user()->isOwner() || $user->role === 'teknisi')
                            <a href="{{ route('users.edit', $user->id) }}"
                               class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 text-slate-600 active:scale-90 transition-transform">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            @if($user->id !== auth()->id())
                                <button type="button"
                                        onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-50 text-red-500 active:scale-90 transition-transform">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="p-12 text-center">
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


{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 relative z-10 animate-fade-in-up">
            <div class="flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mb-4">
                    <i data-lucide="alert-triangle" class="w-6 h-6 text-red-500"></i>
                </div>
                <h3 class="text-lg font-black text-slate-900 mb-2">Hapus Pengguna?</h3>
                <p class="text-sm text-slate-500 font-medium mb-6">
                    Anda yakin ingin menghapus <strong id="deleteUserName" class="text-slate-800"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
                <div class="flex gap-3 w-full">
                    <button onclick="closeDeleteModal()"
                            class="flex-1 px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <form id="deleteForm" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-xl bg-red-500 text-white text-sm font-bold hover:bg-red-600 transition-colors">
                            Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function confirmDelete(userId, userName) {
        document.getElementById('deleteUserName').textContent = userName;
        document.getElementById('deleteForm').action = `/users/${userId}`;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>
@endpush
