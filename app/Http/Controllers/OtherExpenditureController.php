<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ActivityLog;
use App\Models\OtherExpenditure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use App\Services\ImageCompressionService;


class OtherExpenditureController extends Controller
{
    private ImageCompressionService $compression;

    public function __construct(ImageCompressionService $compression)
    {
        $this->compression = $compression;
    }

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

    private function checkPriveApprovalAccess(): void
    {
        if (Auth::user()?->role !== 'owner') {
            abort(403, 'Hanya Owner yang bisa menyetujui atau menolak Prive.');
        }
    }

    private function userCanFullyControlPrive(OtherExpenditure $record): bool
    {
        return $record->jenis === 'prive' && Auth::user()?->role === 'owner';
    }

    private function supportsPriveRecipientName(): bool
    {
        return Schema::hasColumn('other_expenditures', 'recipient_name');
    }

    private function hasDefaultRecipientPlaceholder(?string $recipientName): bool
    {
        return strcasecmp(trim((string) $recipientName), 'Penerima Default') === 0;
    }

    // ─── INDEX ─────────────────────────────────────────────────────────
    public function index(string $jenis, Request $request)
    {
        $this->checkAccess($jenis);

        $config = self::JENIS_CONFIG[$jenis] ?? abort(404);
        
        $query = OtherExpenditure::with([
            'submitter',
            'paidBy',
            'bankAccount',
            'senderBankAccount',
            'branch.bankAccounts',
            'dariBranch.bankAccounts',
            'children' => function ($query) {
                $query->with(['submitter', 'paidBy', 'branch.bankAccounts', 'dariBranch.bankAccounts', 'bankAccount', 'senderBankAccount'])
                    ->orderBy('created_at')
                    ->orderBy('id');
            },
        ]);

        if (in_array($jenis, ['bayar_hutang', 'piutang_usaha'])) {
            $query->whereIn('jenis', ['bayar_hutang', 'piutang_usaha']);
            $query->whereNull('parent_id');
        } else {
            $query->where('jenis', $jenis);
        }

        // Filter: Search (Invoice Number)
        if ($search = $request->input('search')) {
            $hasRecipientName = $this->supportsPriveRecipientName();

            $query->where(function ($q) use ($search, $hasRecipientName) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('rekening_tujuan', 'like', "%{$search}%")
                    ->orWhereHas('children', function ($childQuery) use ($search) {
                        $childQuery->where('invoice_number', 'like', "%{$search}%");
                    });

                if ($hasRecipientName) {
                    $q->orWhere('recipient_name', 'like', "%{$search}%");
                }
            });
        }

        // Filter: Branch
        if ($branchId = $request->input('branch_id')) {
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('dari_cabang_id', $branchId);
            });
        }

        // Filter: Record Status
        if ($recordStatus = $request->input('record_status')) {
            if ($jenis === 'prive' && in_array($recordStatus, ['pending', 'approved', 'rejected'], true)) {
                $query->where('status', $recordStatus);
            } elseif ($recordStatus === 'belum_lunas') {
                // Ada sisa: record parent masih pending ATAU ada anak yang masih pending
                $query->where(function ($q) {
                    $q->where('status', 'pending')
                      ->orWhereHas('children', function ($childQ) {
                          $childQ->where('status', 'pending');
                      });
                });
            } elseif ($recordStatus === 'sudah_lunas') {
                // Semua lunas: parent berstatus approved DAN tidak ada anak yang masih pending
                $query->where('status', 'approved')
                      ->whereDoesntHave('children', function ($childQ) {
                          $childQ->where('status', 'pending');
                      });
            }
        }

        $items = $query->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->paginate(10);
        
        $items->appends($request->all());

        $branchDebts = null;
        if (in_array($jenis, ['bayar_hutang', 'piutang_usaha'])) {
            $debtStatus = $request->input('debt_status');
            $debtSearch = $request->input('debt_search');
            $debtBranchId = $request->input('debt_branch_id');

            $debtQuery = \App\Models\BranchDebt::with([
                    'transaction',
                    'debtorBranch.bankAccounts',
                    'creditorBranch.bankAccounts',
                    'paidBy',
                    'bankAccount',
                    'senderBankAccount',
                    'children' => function ($query) {
                        $query->with(['transaction', 'debtorBranch.bankAccounts', 'creditorBranch.bankAccounts', 'paidBy', 'bankAccount', 'senderBankAccount'])
                            ->orderBy('created_at')
                            ->orderBy('id');
                    },
                ])
                ->whereNull('parent_id')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            if (in_array($debtStatus, ['pending', 'paid'])) {
                if ($debtStatus === 'pending') {
                    $debtQuery->where(function ($q) {
                        $q->where('status', 'pending')
                            ->orWhereHas('children', function ($childQuery) {
                                $childQuery->where('status', 'pending');
                            });
                    });
                } else {
                    $debtQuery->where('status', 'paid')
                        ->whereDoesntHave('children', function ($childQuery) {
                            $childQuery->where('status', 'pending');
                        });
                }
            }

            // Filter: Search for BranchDebt (related transaction invoice number)
            if ($debtSearch) {
                $debtQuery->whereHas('transaction', function($q) use ($debtSearch) {
                    $q->where('invoice_number', 'like', "%{$debtSearch}%");
                });
            }

            // Filter: Branch for BranchDebt
            if ($debtBranchId) {
                if ($jenis === 'bayar_hutang') {
                    // Di halaman Bayar Hutang, fokus pada siapa yang berhutang
                    $debtQuery->where('debtor_branch_id', $debtBranchId);
                } else {
                    // Di halaman Piutang Usaha, fokus pada siapa yang menerima piutang
                    $debtQuery->where('creditor_branch_id', $debtBranchId);
                }
            }

            $branchDebts = $debtQuery->paginate(10, ['*'], 'branch_debts');
            $branchDebts->appends($request->all());
        }

        $branches = Branch::orderBy('name')->get();

        $folder = $jenis === 'piutang_usaha'
            ? 'bayar-hutang'
            : str_replace('_', '-', $jenis);
        return view("pengeluaran-lain.{$folder}.index", compact('jenis', 'config', 'items', 'branchDebts', 'branches'));
    }

    // ─── CREATE ────────────────────────────────────────────────────────
    public function create(string $jenis)
    {
        $this->checkAccess($jenis);

        $config   = self::JENIS_CONFIG[$jenis] ?? abort(404);
        $branches = Branch::orderBy('name')->get();

        $folder = str_replace('_', '-', $jenis);
        return view("pengeluaran-lain.{$folder}.create", compact('jenis', 'config', 'branches'));
    }

    private function ensureManualRecordCanBeChanged(OtherExpenditure $record): void
    {
        $this->checkAccess($record->jenis);

        if ($this->userCanFullyControlPrive($record)) {
            return;
        }

        if ($record->parent_id !== null || $record->status !== 'pending' || $record->children()->exists()) {
            abort(403, 'Record ini tidak bisa diedit atau dihapus karena sudah diproses atau memiliki cicilan.');
        }
    }

    public function edit(int $id)
    {
        $record = OtherExpenditure::findOrFail($id);
        $this->ensureManualRecordCanBeChanged($record);

        $jenis = $record->jenis;
        $config = self::JENIS_CONFIG[$jenis] ?? abort(404);
        $branches = Branch::orderBy('name')->get();
        $folder = str_replace('_', '-', $jenis);

        return view("pengeluaran-lain.{$folder}.create", compact('jenis', 'config', 'branches', 'record'));
    }

    public function update(Request $request, int $id)
    {
        $record = OtherExpenditure::findOrFail($id);
        $this->ensureManualRecordCanBeChanged($record);

        $jenis = $record->jenis;
        $config = self::JENIS_CONFIG[$jenis] ?? abort(404);

        $rules = [
            'tanggal'    => 'required|date',
            'nominal'    => 'required|numeric|min:1',
            'keterangan' => 'nullable|string|max:500',
        ];

        if (in_array($jenis, ['bayar_hutang', 'piutang_usaha'], true)) {
            $rules['branch_id'] = ['required', 'exists:branches,id'];
            $rules['dari_cabang_id'] = ['required', 'exists:branches,id', 'different:branch_id'];
        }

        if ($jenis === 'prive') {
            $rules['payment_method'] = ['required', 'in:transfer,cash'];
            $rules['recipient_name'] = ['required', 'string', 'max:255'];
            $rules['rekening_tujuan'] = ['required_if:payment_method,transfer', 'nullable', 'string', 'max:255'];
            $rules['dari_cabang_id'] = ['required', 'exists:branches,id'];
        }

        $validated = $request->validate($rules, [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'nominal.required' => 'Nominal wajib diisi.',
            'branch_id.required' => 'Cabang penerima wajib dipilih.',
            'dari_cabang_id.required' => 'Cabang penyalur wajib dipilih.',
            'dari_cabang_id.different' => 'Cabang penyalur dan cabang penerima tidak boleh sama.',
            'payment_method.required' => 'Metode Prive wajib dipilih.',
            'payment_method.in' => 'Metode Prive tidak valid.',
            'recipient_name.required' => 'Nama penerima wajib diisi.',
            'rekening_tujuan.required_if' => 'Tujuan transfer wajib diisi untuk metode transfer.',
        ]);

        if ($jenis === 'prive' && $this->hasDefaultRecipientPlaceholder($validated['recipient_name'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'recipient_name' => 'Nama penerima wajib diisi dengan nama sebenarnya.',
            ]);
        }

        $data = [
            'tanggal'    => $validated['tanggal'],
            'nominal'    => $validated['nominal'],
            'keterangan' => $validated['keterangan'] ?? null,
        ];

        if (in_array($jenis, ['bayar_hutang', 'piutang_usaha'], true)) {
            $data['branch_id'] = $validated['branch_id'];
            $data['dari_cabang_id'] = $validated['dari_cabang_id'];
        }

        if ($jenis === 'prive') {
            $data['payment_method'] = $validated['payment_method'];
            $data['rekening_tujuan'] = $validated['payment_method'] === 'transfer'
                ? $validated['rekening_tujuan']
                : null;
            $data['dari_cabang_id'] = $validated['dari_cabang_id'];

            if ($this->supportsPriveRecipientName()) {
                $data['recipient_name'] = $validated['recipient_name'];
            }
        }

        $record->update($data);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'edit',
            'target_id' => $record->invoice_number,
            'description' => "Mengedit {$config['label']} {$record->invoice_number} sebesar Rp " . number_format((int) $record->nominal, 0, ',', '.') . ($jenis === 'prive' ? " untuk " . ($record->recipient_name ?? '-') : '') . " | Oleh: " . auth()->user()->name,
        ]);

        $jenisRoute = str_replace('_', '-', $jenis);

        return redirect()
            ->route("pengeluaran-lain.{$jenisRoute}.index")
            ->with('notification', "✅ {$config['label']} {$record->invoice_number} berhasil diperbarui.");
    }

    // ─── STORE ─────────────────────────────────────────────────────────
    public function store(Request $request, string $jenis)
    {
        $this->checkAccess($jenis);

        // Pastikan jenis valid
        $config = self::JENIS_CONFIG[$jenis] ?? abort(404);

        // Validasi dasar
        $rules = [
            'tanggal'        => 'required|date',
            'nominal'        => 'required|numeric|min:1',
            'keterangan'     => 'nullable|string|max:500',
            'bukti_transfer' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        if (in_array($jenis, ['bayar_hutang', 'piutang_usaha'])) {
            $rules['branch_id'] = [
                'required',
                'exists:branches,id',
            ];

            $rules['dari_cabang_id'] = [
                'required',
                'exists:branches,id',
                'different:branch_id',
            ];
        }

        if ($jenis === 'prive') {
            $rules['payment_method'] = [
                'required',
                'in:transfer,cash',
            ];

            $rules['recipient_name'] = [
                'required',
                'string',
                'max:255',
            ];

            $rules['rekening_tujuan'] = [
                'required_if:payment_method,transfer',
                'nullable',
                'string',
                'max:255',
            ];

            $rules['dari_cabang_id'] = [
                'required',
                'exists:branches,id',
            ];
        }

        $validator = Validator::make($request->all(), $rules, [
            'tanggal.required'         => 'Tanggal wajib diisi.',
            'tanggal.date'             => 'Format tanggal tidak valid.',

            'nominal.required'         => 'Nominal wajib diisi.',
            'nominal.numeric'          => 'Nominal harus berupa angka.',
            'nominal.min'              => 'Nominal minimal harus lebih dari 0.',

            'branch_id.required'       => 'Cabang penerima wajib dipilih.',
            'branch_id.exists'         => 'Cabang penerima tidak valid.',

            'dari_cabang_id.required'  => 'Cabang penyalur wajib dipilih.',
            'dari_cabang_id.exists'    => 'Cabang penyalur tidak valid.',
            'dari_cabang_id.different' => 'Cabang penyalur dan cabang penerima tidak boleh sama.',

            'bukti_transfer.image'     => 'Bukti transfer harus berupa gambar.',
            'bukti_transfer.mimes'     => 'Bukti transfer harus berupa file JPG, JPEG, atau PNG.',
            'bukti_transfer.max'       => 'Ukuran bukti transfer maksimal 2MB.',
            'payment_method.required'   => 'Metode Prive wajib dipilih.',
            'payment_method.in'         => 'Metode Prive tidak valid.',
            'recipient_name.required'   => 'Nama penerima wajib diisi.',
            'rekening_tujuan.required_if' => 'Tujuan transfer wajib diisi untuk metode transfer.',
        ]);

        $validator->after(function ($validator) use ($request, $jenis) {
            if (
                in_array($jenis, ['bayar_hutang', 'piutang_usaha'], true) &&
                $request->filled('branch_id') &&
                $request->filled('dari_cabang_id') &&
                (string) $request->branch_id === (string) $request->dari_cabang_id
            ) {
                $validator->errors()->add(
                    'dari_cabang_id',
                    'Cabang penyalur dan cabang penerima tidak boleh sama.'
                );

                $validator->errors()->add(
                    'branch_id',
                    'Cabang penerima tidak boleh sama dengan cabang penyalur.'
                );
            }
        });

        $validator->after(function ($validator) use ($request, $jenis) {
            if ($jenis === 'prive' && $this->hasDefaultRecipientPlaceholder($request->input('recipient_name'))) {
                $validator->errors()->add(
                    'recipient_name',
                    'Nama penerima wajib diisi dengan nama sebenarnya.'
                );
            }
        });

        $validated = $validator->validate();

        $filePath = null;

        try {
            // Generate invoice number cukup satu kali
            $invoiceNumber = OtherExpenditure::generateInvoiceNumber($jenis);

            // Upload bukti transfer
            if ($request->hasFile('bukti_transfer')) {
                $file = $request->file('bukti_transfer');
                $ext  = $file->getClientOriginalExtension();

                $filePath = "pengeluaran-lain/{$invoiceNumber}.{$ext}";

                Storage::disk('public')->put($filePath, file_get_contents($file));

                // Kompres gambar setelah disimpan
                $this->compression->compressUpload(
                    Storage::disk('public')->path($filePath)
                );
            }

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
                $data['branch_id']      = $validated['branch_id'];
                $data['dari_cabang_id'] = $validated['dari_cabang_id'];
            }

            if ($jenis === 'prive') {
                $data['payment_method']  = $validated['payment_method'];
                $data['rekening_tujuan'] = $validated['payment_method'] === 'transfer'
                    ? $validated['rekening_tujuan']
                    : null;
                $data['dari_cabang_id']  = $validated['dari_cabang_id'];

                if ($this->supportsPriveRecipientName()) {
                    $data['recipient_name'] = $validated['recipient_name'];
                }
            }

            $record = OtherExpenditure::create($data);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'target_id' => $record->invoice_number,
                'description' => "Membuat pengajuan {$config['label']} {$record->invoice_number} sebesar Rp " . number_format((int) $record->nominal, 0, ',', '.') . " via " . ($record->payment_method === 'cash' ? 'Cash' : 'Transfer') . " untuk " . ($validated['recipient_name'] ?? '-') . " dari cabang " . ($record->dariBranch->name ?? '-') . " | Oleh: " . auth()->user()->name,
            ]);

            Log::info("[PengeluaranLain] {$jenis} created", [
                'id'             => $record->id,
                'invoice_number' => $record->invoice_number,
                'by'             => Auth::id(),
            ]);

            $jenisLabel = OtherExpenditure::JENIS[$jenis] ?? $config['label'];
            $jenisRoute = str_replace('_', '-', $jenis);

            return redirect()
                ->route("pengeluaran-lain.{$jenisRoute}.index")
                ->with('notification', "✅ {$jenisLabel} berhasil disimpan: {$invoiceNumber}");

        } catch (\Throwable $e) {
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            Log::error("[PengeluaranLain] Failed to create {$jenis}", [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'error' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.',
                ]);
        }
    }

    // ─── SHOW (Image serve) ────────────────────────────────────────────
    public function image(int $id)
    {
        $record = OtherExpenditure::findOrFail($id);
        $this->checkAccess($record->jenis);

        if (!$record->bukti_transfer || !Storage::disk('public')->exists($record->bukti_transfer)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($record->bukti_transfer));
    }

    public function destroy(int $id)
    {
        try {
            $record = OtherExpenditure::findOrFail($id);
            $this->ensureManualRecordCanBeChanged($record);

            if ($record->bukti_transfer && Storage::disk('public')->exists($record->bukti_transfer)) {
                Storage::disk('public')->delete($record->bukti_transfer);
            }

            $invoice = $record->invoice_number;
            $jenisLabel = $record->jenis_label;
            $record->delete();

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'target_id' => $invoice,
                'description' => "Menghapus {$jenisLabel} {$invoice} | Oleh: " . auth()->user()->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Record {$invoice} berhasil dihapus.",
            ]);
        } catch (\Throwable $e) {
            $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Gagal menghapus record.',
            ], $status);
        }
    }

    // ─── SETTLE (Bayar Hutang / Cairkan Piutang) ────────────────────────
    public function settle(Request $request, $id)
    {
        try {
            $record = OtherExpenditure::findOrFail($id);
            $this->checkAccess($record->jenis);

            if ($record->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi ini sudah lunas.',
                ], 400);
            }

            $isCash = $request->input('payment_method') === 'cash';
            $paymentType = $request->input('payment_type', 'lunas');
            $isCicil = $paymentType === 'cicil';

            // Validasi conditional: Cash → proof & rekening opsional; Transfer → wajib
            $rules = [
                'payment_method' => 'required|in:transfer,cash',
                'payment_type'   => 'required|in:lunas,cicil',
                'notes'          => 'nullable|string',
            ];
            $messages = [];

            if ($isCicil) {
                $rules['amount_paid'] = 'required|numeric|min:1|max:' . ($record->nominal - 1);
                $messages['amount_paid.required'] = 'Nominal cicilan wajib diisi.';
                $messages['amount_paid.numeric'] = 'Nominal cicilan harus berupa angka.';
                $messages['amount_paid.min'] = 'Nominal cicilan minimal Rp 1.';
                $messages['amount_paid.max'] = 'Nominal cicilan harus lebih kecil dari total hutang.';
            }

            if (!$isCash) {
                $rules['payment_proof']          = 'required|file|mimes:jpg,jpeg,png,pdf|max:10240';
                $rules['bank_account_id']        = 'required|exists:branch_bank_accounts,id';
                $rules['sender_bank_account_id'] = 'required|exists:branch_bank_accounts,id';
                $messages = array_merge($messages, [
                    'payment_proof.required'          => 'Bukti transfer wajib diunggah.',
                    'payment_proof.max'               => 'Ukuran bukti transfer maksimal 10MB.',
                    'bank_account_id.required'        => 'Rekening tujuan wajib dipilih.',
                    'sender_bank_account_id.required' => 'Rekening pengirim wajib dipilih.',
                ]);
            } else {
                $rules['payment_proof'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240';
            }

            $request->validate($rules, $messages);

            // Database transaction to ensure atomicity
            \Illuminate\Support\Facades\DB::beginTransaction();

            $filePath = $record->bukti_transfer;
            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');
                $ext  = $file->getClientOriginalExtension();
                $invoiceClean = preg_replace('/[^A-Za-z0-9_-]+/', '-', $record->invoice_number);
                $filePath = "pengeluaran-lain/{$invoiceClean}_payment_" . now()->format('Ymd-His') . ".{$ext}";

                Storage::disk('public')->put($filePath, file_get_contents($file));

                // Kompres gambar setelah disimpan (skip PDF)
                if (strtolower($ext) !== 'pdf') {
                    $this->compression->compressUpload(
                        Storage::disk('public')->path($filePath)
                    );
                }
            }

            $notesText = $request->notes ? trim($request->notes) : '';
            $currentKeterangan = $record->keterangan ? trim($record->keterangan) : '';
            $newKeterangan = $currentKeterangan;

            $originalNominal = $record->nominal;
            $amountPaid = $isCicil ? (int) $request->amount_paid : $originalNominal;

            if ($isCicil) {
                $cicilNote = "Pembayaran Cicilan: Rp " . number_format($amountPaid, 0, ',', '.') . " dari Total Rp " . number_format($originalNominal, 0, ',', '.');
                if ($notesText !== '') {
                    $cicilNote .= " (Catatan: " . $notesText . ")";
                }
                $newKeterangan = $currentKeterangan !== '' ? $currentKeterangan . "\n" . $cicilNote : $cicilNote;

                // Create remainder record
                $nominalRemainder = $originalNominal - $amountPaid;
                $newInvoiceNumber = OtherExpenditure::generateInvoiceNumber($record->jenis);

                $remainderNotes = "Sisa cicilan dari " . $record->invoice_number;
                if ($currentKeterangan !== '') {
                    $remainderNotes = $currentKeterangan . " (Sisa cicilan dari " . $record->invoice_number . ")";
                }

                $remainderData = [
                    'parent_id'      => $record->parent_id ?? $record->id,
                    'invoice_number' => $newInvoiceNumber,
                    'jenis'          => $record->jenis,
                    'tanggal'        => $record->tanggal,
                    'nominal'        => $nominalRemainder,
                    'keterangan'     => $remainderNotes,
                    'bukti_transfer' => null,
                    'submitted_by'   => $record->submitted_by,
                    'status'         => 'pending',
                    'branch_id'      => $record->branch_id,
                    'dari_cabang_id' => $record->dari_cabang_id,
                    'rekening_tujuan'=> $record->rekening_tujuan,
                    'payment_method' => $record->payment_method,
                ];

                if ($this->supportsPriveRecipientName()) {
                    $remainderData['recipient_name'] = $record->recipient_name;
                }

                OtherExpenditure::create($remainderData);
            } else {
                if ($notesText !== '') {
                    $newKeterangan = $currentKeterangan !== '' ? $currentKeterangan . "\nCatatan Pembayaran: " . $notesText : "Catatan Pembayaran: " . $notesText;
                }
            }

            $record->update([
                'status'                 => 'approved', // Status becomes 'approved' (Sudah Lunas)
                'nominal'                => $amountPaid,
                'bukti_transfer'         => $filePath,
                'payment_method'         => $request->payment_method,
                'bank_account_id'        => $isCash ? null : (int) $request->bank_account_id,
                'sender_bank_account_id' => $isCash ? null : (int) $request->sender_bank_account_id,
                'keterangan'             => $newKeterangan,
                'paid_by'                => auth()->id(),
                'paid_at'                => now(),
            ]);

            // Log activity
            $logLabel = $isCicil ? 'Cicilan ' . ($record->jenis === 'bayar_hutang' ? 'Hutang' : 'Piutang') : ($record->jenis === 'bayar_hutang' ? 'Hutang' : 'Piutang');
            $formattedAmountPaid = 'Rp ' . number_format($amountPaid, 0, ',', '.');

            \App\Models\ActivityLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'approve',
                'target_id'   => $record->invoice_number,
                'description' => "Melunaskan {$logLabel} {$record->invoice_number} sebesar {$formattedAmountPaid} via " . ($isCash ? 'Tunai (Cash)' : 'Transfer') . ($request->notes ? " (Catatan: {$request->notes})" : '') . " | Diproses oleh: " . auth()->user()->name,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isCicil
                    ? "Cicilan sebesar {$formattedAmountPaid} berhasil dibayarkan. Sisa sisa hutang telah dibuat sebagai transaksi baru."
                    : "Transaksi {$record->invoice_number} berhasil dilunaskan.",
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors'  => $e->validator->errors()
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error("[PengeluaranLain] Settle failed", [
                'id'      => $id,
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal melunaskan transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function history(int $id)
    {
        try {
            $record = OtherExpenditure::findOrFail($id);
            $this->checkAccess($record->jenis);

            $ancestorId = $record->parent_id ?? $record->id;
            $history = OtherExpenditure::with(['paidBy', 'submitter', 'branch', 'dariBranch'])
                ->where('parent_id', $ancestorId)
                ->orWhere('id', $ancestorId)
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil history: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approvePrive(int $id)
    {
        try {
            $record = OtherExpenditure::with('dariBranch')->findOrFail($id);
            $this->checkAccess($record->jenis);
            $this->checkPriveApprovalAccess();

            if ($record->jenis !== 'prive') {
                abort(404);
            }

            if ($record->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Prive ini sudah diproses.',
                ], 400);
            }

            $record->update([
                'status' => 'approved',
                'paid_by' => auth()->id(),
                'paid_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'approve',
                'target_id' => $record->invoice_number,
                'description' => "Menyetujui Prive {$record->invoice_number} sebesar Rp " . number_format((int) $record->nominal, 0, ',', '.') . " via " . ($record->payment_method === 'cash' ? 'Cash' : 'Transfer') . " untuk " . ($record->recipient_name ?? '-') . " dari cabang " . ($record->dariBranch->name ?? '-') . " | Owner: " . auth()->user()->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Prive {$record->invoice_number} berhasil disetujui.",
            ]);
        } catch (\Throwable $e) {
            $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Gagal menyetujui Prive.',
            ], $status);
        }
    }

    public function rejectPrive(Request $request, int $id)
    {
        try {
            $record = OtherExpenditure::with('dariBranch')->findOrFail($id);
            $this->checkAccess($record->jenis);
            $this->checkPriveApprovalAccess();

            if ($record->jenis !== 'prive') {
                abort(404);
            }

            if ($record->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Prive ini sudah diproses.',
                ], 400);
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:500',
            ], [
                'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
                'rejection_reason.max' => 'Alasan penolakan maksimal 500 karakter.',
            ]);

            $reason = trim($validated['rejection_reason'] ?? '');
            $currentKeterangan = $record->keterangan ? trim($record->keterangan) : '';
            $newKeterangan = $currentKeterangan;

            if ($reason !== '') {
                $newKeterangan = $currentKeterangan !== ''
                    ? $currentKeterangan . "\nAlasan Penolakan: " . $reason
                    : "Alasan Penolakan: " . $reason;
            }

            $record->update([
                'status' => 'rejected',
                'keterangan' => $newKeterangan,
                'paid_by' => auth()->id(),
                'paid_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'reject',
                'target_id' => $record->invoice_number,
                'description' => "Menolak Prive {$record->invoice_number} sebesar Rp " . number_format((int) $record->nominal, 0, ',', '.') . " untuk " . ($record->recipient_name ?? '-') . ($reason !== '' ? " dengan alasan: {$reason}" : '') . " | Owner: " . auth()->user()->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Prive {$record->invoice_number} berhasil ditolak.",
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Throwable $e) {
            $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Gagal menolak Prive.',
            ], $status);
        }
    }
}
