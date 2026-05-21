<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramNotificationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_cash_payment_does_not_require_private_telegram(): void
    {
        Config::set('services.telegram.bot_token', 'test-token');
        Config::set('services.telegram.group_monitoring_id', '-100management');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $payer = User::factory()->admin()->create();
        $submitter = User::factory()->owner()->create(['telegram_chat_id' => null]);
        $transaction = Transaction::factory()->rembush()->create([
            'submitted_by' => $submitter->id,
            'status' => 'waiting_payment',
            'amount' => 125000,
            'upload_id' => 'TEST-INTERNAL-CASH',
            'invoice_number' => 'INV-INTERNAL-CASH',
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->actingAs($payer)->postJson('/api/v1/payment/cash/upload', [
            'upload_id' => $transaction->upload_id,
            'transaksi_id' => (string) $transaction->id,
            'catatan' => 'Cash internal test',
        ]);

        $response->assertAccepted()
            ->assertJson([
                'success' => true,
                'status' => 'completed',
            ]);

        $this->assertSame('completed', $transaction->fresh()->status);

        Http::assertSentCount(0);
    }

    public function test_technician_cash_payment_still_requires_private_telegram(): void
    {
        $payer = User::factory()->admin()->create();
        $submitter = User::factory()->create([
            'role' => 'teknisi',
            'telegram_chat_id' => null,
        ]);
        $transaction = Transaction::factory()->rembush()->create([
            'submitted_by' => $submitter->id,
            'status' => 'waiting_payment',
            'amount' => 125000,
            'upload_id' => 'TEST-TECH-CASH',
            'invoice_number' => 'INV-TECH-CASH',
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->actingAs($payer)->postJson('/api/v1/payment/cash/upload', [
            'upload_id' => $transaction->upload_id,
            'transaksi_id' => (string) $transaction->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('waiting_payment', $transaction->fresh()->status);
    }

    public function test_technician_transfer_upload_does_not_require_private_telegram(): void
    {
        Storage::fake('public');
        Config::set('services.n8n.webhook_url', 'https://n8n.test');
        Config::set('services.n8n.secret', 'secret');
        Http::fake(['n8n.test/*' => Http::response(['ok' => true], 200)]);

        $payer = User::factory()->admin()->create();
        $submitter = User::factory()->create([
            'role' => 'teknisi',
            'telegram_chat_id' => null,
        ]);
        $transaction = Transaction::factory()->rembush()->create([
            'submitted_by' => $submitter->id,
            'status' => 'waiting_payment',
            'amount' => 125000,
            'payment_method' => 'transfer_teknisi',
            'upload_id' => 'TEST-TECH-TRANSFER',
            'invoice_number' => 'INV-TECH-TRANSFER',
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->actingAs($payer)->postJson('/api/v1/payment/transfer/upload', [
            'upload_id' => $transaction->upload_id,
            'transaksi_id' => (string) $transaction->id,
            'expected_nominal' => 125000,
            'file' => UploadedFile::fake()->image('transfer.jpg'),
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'waiting_payment',  // ✅ FIX: Status valid
            ]);
    }

    public function test_management_alert_goes_to_channel_only(): void
    {
        Config::set('services.telegram.bot_token', 'test-token');
        Config::set('services.telegram.group_monitoring_id', '-100management');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        User::factory()->owner()->create(['telegram_chat_id' => 'owner-private-chat']);
        $submitter = User::factory()->create(['role' => 'teknisi']);
        $transaction = Transaction::factory()->rembush()->create([
            'submitted_by' => $submitter->id,
            'invoice_number' => 'INV-FLAGGED',
            'amount' => 100000,
            'expected_total' => 100000,
            'actual_total' => 90000,
            'selisih' => 10000,
            'flag_reason' => 'Selisih melebihi tolerance 1000',
        ]);

        app(TelegramBotService::class)->notifyFlaggedTransaction($transaction);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['chat_id'] === '-100management'
            && str_contains($request['text'], 'SELISIH NOMINAL TRANSFER'));
        Http::assertNotSent(fn ($request) => $request['chat_id'] === 'owner-private-chat');
    }
}
