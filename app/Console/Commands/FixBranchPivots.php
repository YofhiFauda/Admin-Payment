<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixBranchPivots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fix-branch-pivots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate transaction branch pivot allocation_amount from allocation_percent where amount is 0';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $txs = \App\Models\Transaction::with('branches')->get();
        $count = 0;
        foreach ($txs as $t) {
            $eff = $t->effective_amount;
            if (!$eff) continue;
            foreach ($t->branches as $b) {
                if ($b->pivot->allocation_amount == 0 && $b->pivot->allocation_percent > 0) {
                    $amt = intval(round(($eff * $b->pivot->allocation_percent) / 100));
                    $t->branches()->updateExistingPivot($b->id, ['allocation_amount' => $amt]);
                    $count++;
                }
            }
        }
        $this->info("Updated {$count} branch pivots successfully.");
    }
}
