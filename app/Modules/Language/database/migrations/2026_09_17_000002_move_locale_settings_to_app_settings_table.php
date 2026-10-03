<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('locale_settings')) {
            $urlMode = DB::table('locale_settings')->value('url_mode') ?? AppSetting::MODE_PATH;

            AppSetting::set(AppSetting::URL_MODE, $urlMode);
        }

        Schema::dropIfExists('locale_settings');
    }

    public function down(): void
    {
        Schema::create('locale_settings', function (Blueprint $table) {
            $table->id();
            $table->string('url_mode')->default(AppSetting::MODE_PATH);
            $table->timestamps();
        });

        DB::table('locale_settings')->insert([
            'url_mode' => AppSetting::urlMode(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
