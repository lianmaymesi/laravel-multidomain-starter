<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locale_settings', function (Blueprint $table) {
            $table->id();
            $table->string('url_mode')->default('path');
            $table->timestamps();
        });

        // Single-row settings table — seed the one row up front. Superseded
        // by app_settings (see the later migration that moves this row over
        // and drops this table), kept literal (no App\Models\LocaleSetting
        // reference) since that model no longer exists.
        DB::table('locale_settings')->insert(['url_mode' => 'path']);
    }

    public function down(): void
    {
        Schema::dropIfExists('locale_settings');
    }
};
