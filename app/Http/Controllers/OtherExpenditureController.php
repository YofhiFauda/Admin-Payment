<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\OtherExpenditure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OtherExpenditureController extends Controller
{
    private const JENIS_CONFIG = [
        'bayar_hutang'  => ['label' => 'Bayar Hutang',  'icon' => 'credit-card',    'color' => 'red'],
        'piutang_usaha' => ['label' => 'Piutang Usaha', 'icon' => 'trending-up',    'color' => 'blue'],
        'prive'         => ['label' => 'Prive',          'icon' => 'user-check',     'color' => 'purple'],
    ];

    // ─── Guard: pastikan hanya role yang diizinkan ─────────────────────
    private function checkAccess(string $jenis): void
    {
        $user = Auth::user();
        if ($jenis === 'prive' && !in_array($user->role, ['atasan', 'owner'])) {
            abort(403, 'Hanya Atasan dan Owner yang bisa mengakses Prive.');
        }
        if (!in_array($user->role, ['admin', 'atasan', 'owner'])) {
            abort(403, 'Akses ditolak.');
        }
    }

    // ─── INDEX ─────────────────────────────────────────────────────────
    public function index(string $jenis)
    {
        $this->checkAccess($jenis);

        $config = self::JENIS_CONFIG[$jenis] ?? abort(404);
        $items  = OtherExpenditure::with(['submitter', 'branch', 'dariBranch'])
            ->where('jenis', $jenis)
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('pengeluaran-lain.index', compact('jenis', 'config', 'items'));
    }

    // ─── CREATE ────────────────────────────────────────────────────────
    public function create(string $jenis)
    {
        $this->checkAccess($jenis);

        $config   = self::JENIS_CONFIG[$jenis] ?? abort(404);
        $branches = Branch::orderBy('name')->get();

        return view('pengeluaran-lain.create', compact('jenis', 'config', 'branches'));
    }

    // ─── STORE ─────────────────────────────────────────────────────────
    public function store(Request $request, string $jenis)
    {
        $this->checkAccess($jenis);

        // Validasi dasar
        $rules = [
            'tanggal'       => 'required|date',
            'nominal'       => 'required|numeric|min:1',
            'keterangan'    => 'nullable|string|max:1000',
            'bukti_transfer'=> 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        if (in_array($jenis, ['bayar_hutang', 'piutang_usaha'])) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        if ($jenis === 'prive') {
            $rules['rekening_tujuan'] = 'required|string|max:255';
            $rules['dari_cabang_id']  = 'required|exists:branches,id';
        }

        $validated = $request->validate($rules);

        // Upload bukti transfer
        $filePath = null;
        if ($request->hasFile('bukti_transfer')) {
            $file     = $request->file('bukti_transfer');
            $invoice  = OtherExpenditure::generateInvoiceNumber();
            $ext      = $file->getClientOriginalExtension();
            $filePath = "pengeluaran-lain/{$invoice}.{$ext}";
            Storage::disk('public')->put($filePath, file_get_contents($file));
        }

        // Generate invoice number
        $invoiceNumber = OtherExpenditure::generateInvoiceNumber();

        $data = [
            'invoice_number' => $invoiceNumber,
            'jenis'          => $jenis,
            'tanggal'        => $validated['tanggal'],
            'nominal'        => $validated['nominal'],
            'keterangan'     => $validated['keterangan'] ?? null,
            'bukti_transfer' => $filePath,
            'submitted_by'   => Auth::id(),
            'status'         => 'pending',
        ];

        if (in_array($jenis, ['bayar_hutang', 'piutang_usaha'])) {
            $data['branch_id'] = $validated['branch_id'];
        }

        if ($jenis === 'prive') {
            $data['rekening_tujuan'] = $validated['rekening_tujuan'];
            $data['dari_cabang_id']  = $validated['dari_cabang_id'];
        }

        $record = OtherExpenditure::create($data);

        Log::info("[PengeluaranLain] {$jenis} created", [
            'id'             => $record->id,
            'invoice_number' => $record->invoice_number,
            'by'             => Auth::id(),
        ]);

        $jenisLabel = OtherExpenditure::JENIS[$jenis] ?? $jenis;
        $jenisRoute = str_replace('_', '-', $jenis);

        return redirect()
            ->route("pengeluaran-lain.{$jenisRoute}.index")
            ->with('notification', "✅ {$jenisLabel} berhasil disimpan: {$invoiceNumber}");
    }

    // ─── DESTROY ───────────────────────────────────────────────────────
    public function destroy(int $id)
    {
        $record = OtherExpenditure::findOrFail($id);
        $this->checkAccess($record->jenis);

        if ($record->status !== 'pending') {
            return back()->with('notification', '⚠️ Hanya record dengan status Pending yang bisa dihapus.');
        }

        // Hapus file
        if ($record->bukti_transfer && Storage::disk('public')->exists($record->bukti_transfer)) {
            Storage::disk('public')->delete($record->bukti_transfer);
        }

        $invoiceNumber = $record->invoice_number;
        $jenis         = $record->jenis;
        $jenisRoute    = str_replace('_', '-', $jenis);
        $record->delete();

        return redirect()
            ->route("pengeluaran-lain.{$jenisRoute}.index")
            ->with('notification', "🗑️ Record {$invoiceNumber} berhasil dihapus.");
    }

    // ─── SHOW (Image serve) ────────────────────────────────────────────
    public function image(int $id)
    {
        $record = OtherExpenditure::findOrFail($id);
        $this->checkAccess($record->jenis);

        if (!$record->bukti_transfer || !Storage::disk('public')->exists($record->bukti_transfer)) {
            abort(404);
        }

        return response()->file(storage_path('app/public/' . $record->bukti_transfer));
    }
}
