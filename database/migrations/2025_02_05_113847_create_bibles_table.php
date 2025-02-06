<?php

declare(strict_types=1);

use App\Models\Language;
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
        Schema::create('bibles', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Language::class)->constrained();
            $table->string('title')->index();
            $table->string('version')->index();
            $table->string('version_code')->index();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bibles');
    }
};
