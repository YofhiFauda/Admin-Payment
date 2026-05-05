<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'OLT JETIS'],
            ['name' => 'OLT SIMAN'],
            ['name' => 'OLT SLAHUNG'],
            ['name' => 'OLT SUMBEREJO'],
            ['name' => 'OLT WHUSNET'],
            ['name' => 'OLT PACITAN'],
            ['name' => 'OLT SAWOO'],
            ['name' => 'OLT NGRECO'],
            ['name' => 'OLT CARUBAN'],
            ['name' => 'OLT TEMON'],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(['name' => $branch['name']], $branch);
        }
    }
}
