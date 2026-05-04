<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pulse_aggregates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('bucket');
            $table->unsignedMediumInteger('period');
            $table->string('type');
            $table->mediumText('key');
            $table->char('key_hash', 16);
            $table->string('aggregate');
            $table->decimal('value', 20, 2);

            $table->unique(['bucket', 'period', 'type', 'aggregate', 'key_hash']);
            $table->index('period');
            $table->index('type');
            $table->index(['period', 'bucket']);
            $table->index(['period', 'type', 'key_hash', 'bucket']);
        });

        Schema::create('pulse_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('timestamp');
            $table->string('type');
            $table->mediumText('key');
            $table->char('key_hash', 16)->nullable();
            $table->longText('value')->nullable();

            $table->index('timestamp');
            $table->index('type');
            $table->index('key_hash');
            $table->index(['timestamp', 'type', 'key_hash']);
            $table->index(['timestamp', 'type']);
        });

        Schema::create('pulse_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('timestamp');
            $table->string('type');
            $table->mediumText('key');
            $table->char('key_hash', 16);
            $table->text('value');

            $table->unique(['type', 'key_hash']);
            $table->index('timestamp');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pulse_aggregates');
        Schema::dropIfExists('pulse_entries');
        Schema::dropIfExists('pulse_values');
    }
};
