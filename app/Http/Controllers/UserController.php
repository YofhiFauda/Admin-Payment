<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Daftar pengguna (paginated).
     */
    public function index(Request $request)
    {
        $query = User::query()->orderBy('created_at', 'desc');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Form tambah pengguna baru.
     */
    public function create()
    {
        $availableRoles = $this->getAvailableRoles();
        return view('users.create', compact('availableRoles'));
    }

    /**
     * Simpan pengguna baru.
     */
    public function store(Request $request)
    {
        $availableRoles = $this->getAvailableRoles();

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => ['required', Rule::in(array_keys($availableRoles))],
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter, at least 1 number and 1 capital letter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required'      => 'Role wajib dipilih.',
            'role.in'            => 'Role tidak valid.',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        return redirect()->route('users.index')
            ->with('notification', "Pengguna {$user->name} berhasil didaftarkan.");
    }

    /**
     * Form edit pengguna.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);

        if (!$this->canManageUser($user)) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit pengguna ini.');
        }

        $availableRoles = $this->getAvailableRoles();

        return view('users.edit', compact('user', 'availableRoles'));
    }

    /**
     * Update data pengguna.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        if (!$this->canManageUser($user)) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit pengguna ini.');
        }

        $availableRoles = $this->getAvailableRoles();

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role'     => ['required', Rule::in(array_keys($availableRoles))],
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah terdaftar.',
            'password.min'       => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required'      => 'Role wajib dipilih.',
            'role.in'            => 'Role tidak valid.',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->role  = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')
            ->with('notification', "Pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Hapus pengguna.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if (!$this->canManageUser($user)) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus pengguna ini.');
        }

        // Tidak bisa hapus diri sendiri
        if ($user->id === auth()->id()) {
            return back()->with('notification', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('notification', "Pengguna {$name} berhasil dihapus.");
    }


    /**
     * Cek apakah user saat ini bisa mengelola (edit/hapus) target user.
     * Admin/Atasan hanya bisa kelola role teknisi, Owner bisa semua.
     */
    private function canManageUser(User $targetUser): bool
    {
        if (auth()->user()->isOwner()) {
            return true;
        }

        // Admin & Atasan hanya bisa kelola teknisi
        return $targetUser->role === 'teknisi';
    }

    /**
     * Daftar role yang tersedia berdasarkan role user saat ini.
     */
    private function getAvailableRoles(): array
    {
        if (auth()->user()->isOwner()) {
            return [
                'teknisi' => 'Teknisi',
                'admin'   => 'Admin',
                'atasan'  => 'Atasan',
                'owner'   => 'Owner',
            ];
        }

        // Admin & Atasan hanya bisa tambah teknisi
        return [
            'teknisi' => 'Teknisi',
        ];
    }
}
