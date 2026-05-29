<?php

namespace Database\Factories;

use App\Models\MasterItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MasterItem>
 */
class MasterItemFactory extends Factory
{
    protected $model = MasterItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $items = [
            'Tepung Terigu',
            'Gula Pasir',
            'Kabel Listrik',
            'Kabel Data',
            'Laptop Dell',
            'Mouse Wireless',
            'Keyboard Mechanical',
            'Monitor LED',
            'Printer Laser',
            'Scanner Document',
        ];
        
        $name = fake()->randomElement($items);
        $canonicalName = Str::slug($name, ' ');
        
        return [
            'name' => $name,
            'canonical_name' => $canonicalName,
            'display_name' => $name,
            'category_id' => fake()->randomElement(['bahan_baku', 'peralatan', 'elektronik', 'furniture']),
            'approval_status' => 'approved',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the item is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'pending_approval',
        ]);
    }

    /**
     * Indicate that the item is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
