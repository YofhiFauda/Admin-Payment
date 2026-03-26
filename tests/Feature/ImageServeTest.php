<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teknisi_can_access_own_transaction_image()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('nota.jpg');
        $path = $file->store('transactions', 'public');

        $teknisi = User::factory()->create(['role' => 'teknisi']);
        $transaction = Transaction::factory()->create([
            'submitted_by' => $teknisi->id,
            'file_path' => $path,
        ]);

        $response = $this->actingAs($teknisi)
                         ->get(route('transactions.image', $transaction->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_teknisi_can_access_other_transaction_image()
    {
        // Even though they can't list them, if they have the ID, serveImage allows it (current behavior)
        Storage::fake('public');
        $file = UploadedFile::fake()->image('nota.jpg');
        $path = $file->store('transactions', 'public');

        $admin = User::factory()->create(['role' => 'admin']);
        $teknisi = User::factory()->create(['role' => 'teknisi']);
        
        $transaction = Transaction::factory()->create([
            'submitted_by' => $admin->id,
            'file_path' => $path,
        ]);

        $response = $this->actingAs($teknisi)
                         ->get(route('transactions.image', $transaction->id));

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_image()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('nota.jpg');
        $path = $file->store('transactions', 'public');

        $transaction = Transaction::factory()->create([
            'file_path' => $path,
        ]);

        $response = $this->get(route('transactions.image', $transaction->id));

        $response->assertRedirect(route('login'));
    }
}
