<?php

namespace App\Jobs\PriceIndex;

use App\Models\PriceAnomaly;
use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendPriceAnomalyNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $tries = 3;
    public int    $timeout = 60;

    public function __construct(public int $anomalyId)
    {
        $this->onQueue('notifications');
    }

    public function handle(TelegramBotService $telegram): void
    {
        $anomaly = PriceAnomaly::with(['transaction.submitter', 'reporter'])->find($this->anomalyId);

        if (!$anomaly) {
            Log::warning('[PriceAnomalyJob] Anomaly not found', ['id' => $this->anomalyId]);
            return;
        }

        // 1. Notifikasi Telegram (Existing)
        $telegram->notifyPriceAnomaly($anomaly);

        // 2. Notifikasi Database (Internal App)
        $managementUsers = User::whereIn('role', ['owner', 'atasan'])->get();
        \Illuminate\Support\Facades\Notification::send($managementUsers, new \App\Notifications\PriceAnomalyNotification($anomaly));

        // 3. Broadcast Real-time (WebSockets)
        broadcast(new \App\Events\PriceAnomalyDetected($anomaly));

        // Update notified timestamp
        $anomaly->update(['notification_sent_at' => now()]);

        Log::info('📨 [PriceAnomalyJob] All notifications processed', [
            'anomaly_id'    => $this->anomalyId,
            'item_name'     => $anomaly->item_name,
            'recipients_count' => $managementUsers->count(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('❌ [PriceAnomalyJob] Failed', [
            'anomaly_id' => $this->anomalyId,
            'error'      => $e->getMessage(),
        ]);
    }
}
