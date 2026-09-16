<?php

use App\Models\LocaleSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locale_settings', function (Blueprint $table) {
            $table->id();
            $table->string('url_mode')->default(LocaleSetting::MODE_PATH);
            $table->timestamps();
        });

        // Single-row settings table — seed the one row up front so
        // LocaleSetting::current() never has to decide what "no row yet"
        // means.
        LocaleSetting::create(['url_mode' => LocaleSetting::MODE_PATH]);
    }

    public function down(): void
    {
        Schema::dropIfExists('locale_settings');
    }
};
