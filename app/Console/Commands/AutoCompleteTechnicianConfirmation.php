<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\ActivityLog;
use App\Events\TransactionUpdated;
use App\Services\Telegram\TelegramBotService;
use Carbon\Carbon;

class AutoCompleteTechnicianConfirmation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-complete-technician-confirmation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis menyelesaikan transaksi yang menggantung di status pending_technician lebih dari 2 hari';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegramService)
    {
        $this->info("Mencari transaksi pending_technician yang lebih dari 2 hari...");

        // Ambil transaksi yang statusnya pending_technician dan paid_at sudah lewat 48 jam
        // Kita gunakan paid_at karena itu di-set saat Admin mengunggah bukti Cash
        $threshold = Carbon::now()->subDays(2);
        
        $transactions = Transaction::where('status', 'pending_technician')
            ->whereNotNull('paid_at')
            ->where('paid_at', '<=', $threshold)
            ->with('submitter')
            ->get();

        if ($transactions->isEmpty()) {
            $this->info("✅ Tidak ada transaksi yang perlu di-auto-complete.");
            return;
        }

        $count = 0;
        foreach ($transactions as $transaction) {
            $now = Carbon::now();
            
            $transaction->update([
                'status'         => 'completed',
                'konfirmasi_at'  => $now,
                'description'    => $transaction->description 
                    ? $transaction->description . ' | (Sistem: Otomatis Selesai melebihi batas waktu 2 hari)' 
                    : '(Sistem: Otomatis Selesai melebihi batas waktu 2 hari)',
            ]);

            // Broadcast ke UI
            broadcast(new TransactionUpdated($transaction->fresh()));

            // Buat activity log
            ActivityLog::create([
                'user_id'        => null, // System
                'action'         => 'auto_complete',
                'transaction_id' => $transaction->id,
                'target_id'      => $transaction->invoice_number,
                'description'    => "Sistem otomatis mengubah status menjadi Selesai karena tidak ada konfirmasi teknisi lebih dari 2 hari.",
            ]);

            // Kirim notifikasi Telegram ke Teknisi
            if ($transaction->submitter && $transaction->submitter->telegram_chat_id) {
                $invoiceNumber = $transaction->invoice_number;
                $nominal = 'Rp ' . number_format($transaction->amount, 0, ',', '.');
                $timestamp = $now->format('d/m/Y - H:i') . ' WIB';
                
                $message = <<<HTML
✅ <b>TRANSAKSI OTOMATIS SELESAI</b>

Transaksi Anda (Cash) otomatis diselesaikan oleh sistem karena telah melewati batas waktu konfirmasi 2x24 jam.

<b>Detail:</b>
▪️ Invoice  : <code>{$invoiceNumber}</code>
▪️ Nominal  : {$nominal}
▪️ Waktu    : {$timestamp}

Terima kasih.
HTML;
                $telegramService->sendMessage($transaction->submitter->telegram_chat_id, $message);
            }

            $count++;
            $this->line("Di-auto-complete: {$transaction->invoice_number}");
        }

        $this->info("✅ Proses selesai. {$count} transaksi telah di-auto-complete.");
    }
}
