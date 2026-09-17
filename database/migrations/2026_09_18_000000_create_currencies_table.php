<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->unsignedTinyInteger('decimal_digits')->default(2);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(false);

            // Always relative to the primary currency — the primary's own
            // row is always 1. Null until the first refresh (or forever,
            // for a currency that's never been made active).
            $table->decimal('exchange_rate', 24, 10)->nullable();
            $table->timestamp('rate_synced_at')->nullable();

            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
