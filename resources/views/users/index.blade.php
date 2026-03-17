@extends('layouts.app')

@section('page-title', 'Kelola Pengguna')

@section('content')
<div class="space-y-6">

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500 font-medium">Total {{ $users->total() }} pengguna terdaftar</p>
        </div>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 transition-all active:scale-[0.98]">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Tambah Pengguna
        </a>
    </div>

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
        </div>
        <select name="role" onchange="this.form.submit()"
                class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
            <option value="">Semua Role</option>
            <option value="teknisi" {{ request('role') === 'teknisi' ? 'selected' : '' }}>Teknisi</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="atasan" {{ request('role') === 'atasan' ? 'selected' : '' }}>Atasan</option>
            <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner</option>
        </select>
        <button type="submit"
                class="px-5 py-2.5 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-colors">
            Cari
        </button>
    </form>

    {{-- Desktop Table --}}
    <div class="hidden md:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">#</th>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nama</th>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Email</th>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Role</th>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Terdaftar</th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $index => $user)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-3.5 text-sm text-slate-400 font-medium">{{ $users->firstItem() + $index }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <span class="text-sm font-bold text-slate-800">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-600 font-medium">{{ $user->email }}</td>
                    <td class="px-5 py-3.5">
                        @php
                            $roleColors = [
                                'owner'   => 'bg-amber-50 text-amber-700 border-amber-200',
                                'atasan'  => 'bg-blue-50 text-blue-700 border-blue-200',
                                'admin'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'teknisi' => 'bg-slate-50 text-slate-700 border-slate-200',
                            ];
                        @endphp
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold border {{ $roleColors[$user->role] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-500 font-medium">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3.5">
                        @php
                            $canManage = Auth::user()->isOwner() || $user->role === 'teknisi';
                        @endphp
                        <div class="flex items-center justify-end gap-1">
                            @if($user->role === 'teknisi')
                            <button type="button" onclick="openBankAccountsModal({{ $user->id }})" title="Kelola Rekening"
                                class="p-2 rounded-lg text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <i data-lucide="credit-card" class="w-4 h-4"></i>
                            </button>
                            @endif
                            @if($canManage)
                            <a href="{{ route('users.edit', $user->id) }}" title="Edit"
                               class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition-colors">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <button type="button" title="Hapus"
                                    onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                    class="p-2 rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-500 transition-colors">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                            @endif
                            @else
                            <span class="text-xs text-slate-400 font-medium italic">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-slate-400"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-500">Belum ada pengguna terdaftar</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Card List --}}
    <div class="md:hidden space-y-3">
        @forelse($users as $user)
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500 font-medium truncate">{{ $user->email }}</p>
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
                <span class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-bold border {{ $roleColors[$user->role] ?? 'bg-slate-50 text-slate-600 border-slate-200' }} flex-shrink-0">
                    {{ ucfirst($user->role) }}
                </span>
            </div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-slate-400 font-medium">{{ $user->created_at->format('d M Y') }}</span>
                </div>
                <div class="flex items-center gap-1">
                    @if($user->role === 'teknisi')
                    <button type="button" onclick="openBankAccountsModal({{ $user->id }})"
                            class="p-1.5 rounded-lg text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                    </button>
                    @endif
                    @if($canManage)
                    <a href="{{ route('users.edit', $user->id) }}"
                       class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                    </a>
                    @if($user->id !== auth()->id())
                    <button type="button"
                            onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')"
                            class="p-1.5 rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-500 transition-colors">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                    @endif
                    @else
                    <span class="text-[10px] text-slate-400 font-medium italic">—</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
            <div class="flex flex-col items-center gap-3">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center">
                    <i data-lucide="users" class="w-6 h-6 text-slate-400"></i>
                </div>
                <p class="text-sm font-bold text-slate-500">Belum ada pengguna terdaftar</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
    <div class="flex justify-center">
        {{ $users->links() }}
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
