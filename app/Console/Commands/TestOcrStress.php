<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Branch;
use App\Models\User;
use App\Services\IdGeneratorService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestOcrStress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-ocr-stress {count=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stress test OCR n8n with multiple simultaneous inputs using note 5mb.jpeg';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $this->info("🚀 Starting OCR stress test with $count inputs...");

        // Filter branches as requested: OLT JETIS, OLT TEMON, OLT SIMAN
        $targetBranchNames = ['OLT JETIS', 'OLT TEMON', 'OLT SIMAN'];
        $branches = Branch::whereIn('name', $targetBranchNames)->get();
        
        if ($branches->isEmpty()) {
            $this->error('❌ None of the requested branches (OLT JETIS, OLT TEMON, OLT SIMAN) found in database.');
            return;
        }

        $user = User::first();
        if (!$user) {
            $this->error('❌ No users found in database.');
            return;
        }

        $sourceFile = base_path('note 5mb.jpeg');
        if (!file_exists($sourceFile)) {
            $this->error('❌ Source file "note 5mb.jpeg" not found in project root.');
            return;
        }

        $n8nUrl = trim(config('services.n8n.webhook_url') ?? env('N8N_WEBHOOK'));
        $n8nSecret = config('services.n8n.secret') ?? env('N8N_SECRET');

        if (!$n8nUrl) {
            $this->error('❌ N8N_WEBHOOK is not configured in .env');
            return;
        }

        $this->info("📍 Targeting n8n Webhook: $n8nUrl");
        $this->info("📍 Using Branches: " . $branches->pluck('name')->join(', '));
        $this->info("📍 Category: Beban Konsumsi");
        $this->info("📍 Method: Cash");
        $this->info("📍 Using Submitter: {$user->name} (ID: {$user->id})");

        $requests = [];
        $branchesCount = $branches->count();

        for ($i = 1; $i <= $count; $i++) {
            $seq = IdGeneratorService::nextSequence();
            $uploadId = IdGeneratorService::buildUploadId($seq);
            $invoiceNumber = IdGeneratorService::buildInvoiceNumber($seq);
            $transaksiId = Str::uuid()->toString();
            
            // Pick branch in rotation
            $branch = $branches[($i - 1) % $branchesCount];

            // Load original content and append unique ID to bypass duplicate hashing
            $originalContent = file_get_contents($sourceFile);
            $uniqueContent = $originalContent . "\n# StressTestID: " . $uploadId;

            // Simpan file ke storage agar bisa diakses via URL oleh n8n
            $filename = "notas/stress_{$uploadId}.jpg";
            Storage::disk('public')->put($filename, $uniqueContent);
            
            // Gunakan asset() untuk URL publik
            $fileUrl = asset("storage/{$filename}");

            $expectedNominal = 0 + ($i * 100);

            $transaction = Transaction::create([
                'upload_id'      => $uploadId,
                'invoice_number' => $invoiceNumber,
                'trace_id'       => $transaksiId,
                'file_path'      => $filename,
                'expected_total' => $expectedNominal,
                'amount'         => $expectedNominal,
                'status'         => 'pending',
                'type'           => 'rembush',
                'category'       => 'Beban Konsumsi',
                'payment_method' => 'cash',
                'submitted_by'   => $user->id,
                'ai_status'      => 'queued',
            ]);

            $transaction->branches()->attach($branch->id, [
                'allocation_percent' => 100,
                'allocation_amount'  => $expectedNominal,
            ]);

            $requests[$uploadId] = [
                'upload_id'        => $uploadId,
                'transaksi_id'     => $transaction->id,
                'file_url'         => $fileUrl,
                'expected_nominal' => $expectedNominal,
                'payment_method'   => 'cash',
                'branch_id'        => $branch->id,
                'secret'           => $n8nSecret,
                'callback_url'     => url('/api/ai/auto-fill'),
            ];
            
            $this->comment("   [$i] Prepared: $uploadId | Branch: {$branch->name}");
        }

        $this->info("\n🔥 Dispatching $count requests SIMULTANEOUSLY to n8n...");

        $startTime = microtime(true);

        $responses = Http::pool(fn ($pool) => 
            collect($requests)->map(fn ($data, $uid) => 
                $pool->as($uid)->timeout(60)->post("{$n8nUrl}/webhook/upload-nota", $data)
            )
        );

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->info("\n✅ Dispatch completed in " . round($duration, 2) . " seconds.");
        $this->info("--------------------------------------------------");

        $successCount = 0;
        foreach ($responses as $uploadId => $response) {
            if ($response->successful()) {
                $this->info("🟢 [$uploadId] Status: " . $response->status());
                $successCount++;
            } else {
                $this->error("🔴 [$uploadId] Status: " . $response->status() . " | Error: " . $response->body());
            }
        }

        $this->info("--------------------------------------------------");
        $this->info("Summary: $successCount / $count successfully reached n8n.");
        $this->info("Check your n8n dashboard and Laravel Horizon to monitor OCR processing.");
    }
}
