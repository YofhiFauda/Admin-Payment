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
        $totalAllocatedPercentAmount = 0;
        
        $normalizedBranches = self::normalize($branches);
        
        // Cek apakah total allocation_amount (dari mode Manual) sama persis dengan total transaksi
        // Jika ya, kita percaya pada nominal tersebut agar tidak terjadi selisih pembulatan persentase.
        $totalSubmittedAmount = collect($normalizedBranches)->sum('allocation_amount');
        $trustSubmittedAmount = (intval($totalSubmittedAmount) === intval($effectiveAmount));

        foreach ($normalizedBranches as $branch) {
            $branchId = (int) $branch['branch_id'];
            
            if ($trustSubmittedAmount) {
                // Gunakan nominal manual yang sudah tepat
                $allocAmount = (int) $branch['allocation_amount'];
                // Hitung ulang persentase agar akurat, lalu batasi 2 desimal
                $allocPercent = $effectiveAmount > 0 ? round(($allocAmount / $effectiveAmount) * 100, 2) : 0;
            } else {
                // Fallback: hitung dari persentase (untuk mode Bagi Rata / Persentase)
                $allocPercent = (float) $branch['allocation_percent'];
                $allocAmount = (int) round(($effectiveAmount * $allocPercent) / 100);
            }
            
            $totalAllocatedPercentAmount += $allocAmount;

            $syncData[$branchId] = [
                'allocation_percent' => $allocPercent,
                'allocation_amount' => $allocAmount,
            ];
        }

        // Jika kita menghitung dari persentase, mungkin ada sisa pembulatan 1-2 perak.
        // Tambahkan selisihnya ke cabang terakhir agar totalnya persis sama.
        if (!$trustSubmittedAmount) {
            $diff = (int) $effectiveAmount - $totalAllocatedPercentAmount;
            if ($syncData !== [] && $diff !== 0) {
                $lastBranchId = array_key_last($syncData);
                $syncData[$lastBranchId]['allocation_amount'] += $diff;
            }
        }

        return $syncData;
    }
}
