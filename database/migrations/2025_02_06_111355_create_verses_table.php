<?php

declare(strict_types=1);

use App\Models\Bible;
use App\Models\Section;
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
        Schema::create('verses', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Bible::class)->constrained();
            $table->foreignIdFor(Section::class)->constrained();
            $table->integer('chapter')->unsigned();
            $table->integer('verse_no')->unsigned()->nullable();
            $table->boolean('have_note');
            $table->text('verse')->index();
            $table->integer('order')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verses');
    }
};
