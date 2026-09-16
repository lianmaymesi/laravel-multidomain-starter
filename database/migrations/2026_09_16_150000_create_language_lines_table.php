<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_lines', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();
            $table->string('key');
            $table->json('text');

            // Not part of Laravel's translation resolution (that's `group`) —
            // purely for the backoffice UI to filter and permission-gate by
            // portal, without needing every hardcoded string rewritten into
            // a real per-portal Laravel translation group/namespace.
            $table->string('scope')->default('common')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_lines');
    }
};
