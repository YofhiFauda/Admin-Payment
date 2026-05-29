<?php

namespace Database\Factories;

use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionCategory>
 */
class TransactionCategoryFactory extends Factory
{
    protected $model = TransactionCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['rembush', 'pengajuan', 'gudang'];
        $type = fake()->randomElement($types);
        
        $names = [
            'rembush' => ['Operasional', 'Pembelian Bahan', 'Transport', 'Konsumsi', 'Lain-lain'],
            'pengajuan' => ['Elektrikal', 'Mekanikal', 'Peralatan', 'Furniture', 'Elektronik'],
            'gudang' => ['Pembelian Bahan Baku', 'Pembelian Barang Jadi', 'Pembelian Supplies'],
        ];
        
        return [
            'name' => fake()->randomElement($names[$type]),
            'type' => $type,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the category is for rembush.
     */
    public function rembush(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'rembush',
            'name' => fake()->randomElement(['Operasional', 'Pembelian Bahan', 'Transport', 'Konsumsi']),
        ]);
    }

    /**
     * Indicate that the category is for pengajuan.
     */
    public function pengajuan(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pengajuan',
            'name' => fake()->randomElement(['Elektrikal', 'Mekanikal', 'Peralatan', 'Furniture']),
        ]);
    }

    /**
     * Indicate that the category is for gudang.
     */
    public function gudang(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'gudang',
            'name' => fake()->randomElement(['Pembelian Bahan Baku', 'Pembelian Barang Jadi']),
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
