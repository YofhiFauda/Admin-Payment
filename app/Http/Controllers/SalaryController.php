<?php

namespace App\Http\Controllers;

use App\Models\SalaryRecord;
use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalaryController extends Controller
{
    private function requireRole(array $roles): void
    {
        if (!in_array(Auth::user()->role, $roles)) {
            abort(403, 'Akses ditolak.');
        }
    }

    // ─── INDEX ─────────────────────────────────────────────────────────
    public function index()
    {
        $this->requireRole(['admin', 'atasan', 'owner']);

        $salaries = SalaryRecord::with(['employee', 'submitter', 'approver'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('pengeluaran-lain.gaji.index', compact('salaries'));
    }

    // ─── CREATE ────────────────────────────────────────────────────────
    public function create()
    {
        $this->requireRole(['admin', 'atasan', 'owner']);

        // Hanya tampilkan karyawan dengan role teknisi
        $employees = User::where('role', 'teknisi')->orderBy('name')->get();

        return view('pengeluaran-lain.gaji.create', compact('employees'));
    }

    // ─── STORE ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->requireRole(['admin', 'atasan', 'owner']);

        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'periode'        => 'required|string|max:50',
            'gaji_pokok'     => 'required|integer|min:0',
            'bonus_1'        => 'nullable|integer|min:0',
            'bonus_2'        => 'nullable|integer|min:0',
            'tunjangan'      => 'nullable|integer|min:0',
            'lembur'         => 'nullable|integer|min:0',
            'bensin'         => 'nullable|integer|min:0',
            'lebih_hari'     => 'nullable|integer|min:0',
            'potongan_absen' => 'nullable|integer|min:0',
            'potongan_bon'   => 'nullable|integer|min:0',
            'catatan_atasan' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $salary = SalaryRecord::create([
                'invoice_number' => SalaryRecord::generateInvoiceNumber(),
                'user_id'        => $validated['user_id'],
                'periode'        => $validated['periode'],
                'gaji_pokok'     => $validated['gaji_pokok'],
                'bonus_1'        => $validated['bonus_1'] ?? 0,
                'bonus_2'        => $validated['bonus_2'] ?? 0,
                'tunjangan'      => $validated['tunjangan'] ?? 0,
                'lembur'         => $validated['lembur'] ?? 0,
                'bensin'         => $validated['bensin'] ?? 0,
                'lebih_hari'     => $validated['lebih_hari'] ?? 0,
                'potongan_absen' => $validated['potongan_absen'] ?? 0,
                'potongan_bon'   => $validated['potongan_bon'] ?? 0,
                'catatan_atasan' => $validated['catatan_atasan'] ?? null,
                'status'         => 'draft',
                'submitted_by'   => Auth::id(),
            ]);

            DB::commit();
            Log::info('[Gaji] Created', ['id' => $salary->id, 'by' => Auth::id()]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[Gaji] Store failed', ['error' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('pengeluaran-lain.gaji.show', $salary->id)
            ->with('notification', "✅ Data gaji {$salary->invoice_number} berhasil disimpan. Status: Draft.");
    }

    // ─── SHOW ──────────────────────────────────────────────────────────
    public function show(int $id)
    {
        $this->requireRole(['admin', 'atasan', 'owner']);

        $salary = SalaryRecord::with(['employee', 'submitter', 'approver', 'payer'])
            ->findOrFail($id);

        return view('pengeluaran-lain.gaji.show', compact('salary'));
    }

    // ─── EDIT ──────────────────────────────────────────────────────────
    public function edit(int $id)
    {
        $this->requireRole(['admin', 'atasan', 'owner']);

        $salary = SalaryRecord::findOrFail($id);

        if (!$salary->isEditable()) {
            return back()->with('notification', '⚠️ Gaji yang sudah disetujui tidak dapat diedit.');
        }

        $employees = User::where('role', 'teknisi')->orderBy('name')->get();

        return view('pengeluaran-lain.gaji.edit', compact('salary', 'employees'));
    }

    // ─── UPDATE ────────────────────────────────────────────────────────
    public function update(Request $request, int $id)
    {
        $this->requireRole(['admin', 'atasan', 'owner']);

        $salary = SalaryRecord::findOrFail($id);

        if (!$salary->isEditable()) {
            return back()->with('notification', '⚠️ Gaji yang sudah disetujui tidak dapat diedit.');
        }

        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'periode'        => 'required|string|max:50',
            'gaji_pokok'     => 'required|integer|min:0',
            'bonus_1'        => 'nullable|integer|min:0',
            'bonus_2'        => 'nullable|integer|min:0',
            'tunjangan'      => 'nullable|integer|min:0',
            'lembur'         => 'nullable|integer|min:0',
            'bensin'         => 'nullable|integer|min:0',
            'lebih_hari'     => 'nullable|integer|min:0',
            'potongan_absen' => 'nullable|integer|min:0',
            'potongan_bon'   => 'nullable|integer|min:0',
            'catatan_atasan' => 'nullable|string|max:1000',
        ]);

        $salary->update([
            'user_id'        => $validated['user_id'],
            'periode'        => $validated['periode'],
            'gaji_pokok'     => $validated['gaji_pokok'],
            'bonus_1'        => $validated['bonus_1'] ?? 0,
            'bonus_2'        => $validated['bonus_2'] ?? 0,
            'tunjangan'      => $validated['tunjangan'] ?? 0,
            'lembur'         => $validated['lembur'] ?? 0,
            'bensin'         => $validated['bensin'] ?? 0,
            'lebih_hari'     => $validated['lebih_hari'] ?? 0,
            'potongan_absen' => $validated['potongan_absen'] ?? 0,
            'potongan_bon'   => $validated['potongan_bon'] ?? 0,
            'catatan_atasan' => $validated['catatan_atasan'] ?? null,
        ]);

        return redirect()
            ->route('pengeluaran-lain.gaji.show', $salary->id)
            ->with('notification', '✅ Data gaji berhasil diperbarui.');
    }

    // ─── APPROVE ───────────────────────────────────────────────────────
    public function approve(int $id)
    {
        $this->requireRole(['atasan', 'owner']);

        $salary = SalaryRecord::findOrFail($id);

        if (!$salary->isDraft()) {
            return back()->with('notification', '⚠️ Hanya gaji dengan status Draft yang bisa disetujui.');
        }

        $salary->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        Log::info('[Gaji] Approved', ['id' => $salary->id, 'by' => Auth::id()]);

        return redirect()
            ->route('pengeluaran-lain.gaji.show', $salary->id)
            ->with('notification', "✅ Gaji {$salary->invoice_number} berhasil disetujui.");
    }

    // ─── PAY (Tandai Sudah Dibayar) ────────────────────────────────────
    public function pay(int $id)
    {
        $this->requireRole(['admin', 'atasan', 'owner']);

        $salary = SalaryRecord::with('employee')->findOrFail($id);

        if (!$salary->isApproved()) {
            return back()->with('notification', '⚠️ Hanya gaji yang sudah disetujui yang bisa ditandai sudah dibayar.');
        }

        $salary->update([
            'status'  => 'paid',
            'paid_by' => Auth::id(),
            'paid_at' => now(),
        ]);

        Log::info('[Gaji] Marked as paid', ['id' => $salary->id, 'by' => Auth::id()]);

        // ─── Kirim notifikasi Telegram ke karyawan ─────────────────
        $employee = $salary->employee;
        if ($employee && $employee->telegram_chat_id) {
            try {
                $telegram  = app(TelegramBotService::class);
                $nominal   = 'Rp ' . number_format($salary->total_gaji, 0, ',', '.');
                $paidBy    = Auth::user()->name;
                $timestamp = now()->translatedFormat('d F Y H:i');

                $message = <<<HTML
💸 <b>GAJI ANDA TELAH DIBAYARKAN</b>

📋 <b>Invoice:</b> <code>{$salary->invoice_number}</code>
📅 <b>Periode:</b> {$salary->periode}
👤 <b>Karyawan:</b> {$employee->name}
💰 <b>Total Gaji:</b> <b>{$nominal}</b>
⏰ <b>Waktu Bayar:</b> {$timestamp}
✅ <b>Diproses oleh:</b> {$paidBy}

Gaji Anda untuk periode <b>{$salary->periode}</b> sudah dibayarkan.
Mohon segera konfirmasi jika ada ketidaksesuaian.

<b>Status: LUNAS ✅</b>
HTML;

                $telegram->sendMessage($employee->telegram_chat_id, $message);
            } catch (\Exception $e) {
                Log::warning('[Gaji] Telegram notification failed', [
                    'salary_id' => $salary->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('pengeluaran-lain.gaji.show', $salary->id)
            ->with('notification', "💸 Gaji {$salary->invoice_number} berhasil ditandai sudah dibayar.");
    }

    // ─── DESTROY ───────────────────────────────────────────────────────
    public function destroy(int $id)
    {
        $this->requireRole(['admin', 'atasan', 'owner']);

        $salary = SalaryRecord::findOrFail($id);

        if (!$salary->isEditable()) {
            return back()->with('notification', '⚠️ Gaji yang sudah disetujui tidak bisa dihapus.');
        }

        $invoiceNumber = $salary->invoice_number;
        $salary->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data gaji {$invoiceNumber} berhasil dihapus."
            ]);
        }

        return redirect()
            ->route('pengeluaran-lain.gaji.index')
            ->with('notification', "🗑️ Data gaji {$invoiceNumber} berhasil dihapus.");
    }
}
