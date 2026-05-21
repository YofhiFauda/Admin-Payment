<?php

namespace Tests\Unit;

use App\Support\BranchAllocation;
use PHPUnit\Framework\TestCase;

class BranchAllocationTest extends TestCase
{
    public function test_normalize_merges_duplicate_branch_rows(): void
    {
        $branches = BranchAllocation::normalize([
            ['branch_id' => 1, 'allocation_percent' => 25, 'allocation_amount' => 0],
            ['branch_id' => 2, 'allocation_percent' => 50, 'allocation_amount' => 0],
            ['branch_id' => 1, 'allocation_percent' => 25, 'allocation_amount' => 0],
        ]);

        $this->assertSame([
            ['branch_id' => 1, 'allocation_percent' => 50.0, 'allocation_amount' => 0],
            ['branch_id' => 2, 'allocation_percent' => 50.0, 'allocation_amount' => 0],
        ], $branches);
    }

    public function test_to_sync_data_is_keyed_by_branch_id(): void
    {
        $syncData = BranchAllocation::toSyncData([
            ['branch_id' => 1, 'allocation_percent' => 25],
            ['branch_id' => 2, 'allocation_percent' => 50],
            ['branch_id' => 1, 'allocation_percent' => 25],
        ], 100000);

        $this->assertSame([
            1 => ['allocation_percent' => 50.0, 'allocation_amount' => 50000],
            2 => ['allocation_percent' => 50.0, 'allocation_amount' => 50000],
        ], $syncData);
    }
}
