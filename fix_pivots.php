<?php

namespace Database\Fixes;

use App\Models\Transaction;

class FixBranchPivots
{
    public static function run()
    {
        $txs = Transaction::with('branches')->get();
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
        echo "Updated " . $count . " branch pivots.\n";
    }
}

FixBranchPivots::run();
