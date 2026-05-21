<?php

namespace App\Support;

use Illuminate\Support\Collection;

class BranchAllocation
{
    public static function normalize(?array $branches): array
    {
        return collect($branches ?? [])
            ->filter(fn ($branch) => isset($branch['branch_id']))
            ->groupBy(fn ($branch) => (int) $branch['branch_id'])
            ->map(fn (Collection $rows, int $branchId) => [
                'branch_id' => $branchId,
                'allocation_percent' => $rows->sum(fn ($row) => (float) ($row['allocation_percent'] ?? 0)),
                'allocation_amount' => $rows->sum(fn ($row) => (int) ($row['allocation_amount'] ?? 0)),
            ])
            ->values()
            ->all();
    }

    public static function toSyncData(array $branches, int|float $effectiveAmount): array
    {
        $syncData = [];
        $totalAllocated = 0;

        foreach (self::normalize($branches) as $branch) {
            $branchId = (int) $branch['branch_id'];
            $allocPercent = (float) $branch['allocation_percent'];
            $allocAmount = (int) round(($effectiveAmount * $allocPercent) / 100);
            $totalAllocated += $allocAmount;

            $syncData[$branchId] = [
                'allocation_percent' => $allocPercent,
                'allocation_amount' => $allocAmount,
            ];
        }

        $diff = (int) $effectiveAmount - $totalAllocated;
        if ($syncData !== [] && $diff !== 0) {
            $lastBranchId = array_key_last($syncData);
            $syncData[$lastBranchId]['allocation_amount'] += $diff;
        }

        return $syncData;
    }
}
