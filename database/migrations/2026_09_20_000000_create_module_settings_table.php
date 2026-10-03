<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Runtime overrides for the feature toggles in config/modules.php.
        // A row exists only while a module's state differs from its config default.
        Schema::create('module_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module')->unique();
            $table->boolean('enabled');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_settings');
    }
};
