<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            'Cabang Jakarta Pusat',
            'Cabang Jakarta Selatan',
            'Cabang Bandung',
            'Cabang Surabaya',
            'Cabang Medan',
            'Cabang Makassar',
        ];

        foreach ($branches as $name) {
            Branch::firstOrCreate(['name' => $name]);
        }
    }
}
