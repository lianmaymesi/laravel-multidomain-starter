<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The library: every uploaded file, owned by nothing but its uploader.
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('conversion_of_id')->nullable()->constrained('media')->cascadeOnDelete();
            $table->string('conversion')->nullable();
            $table->string('disk');
            $table->string('path');
            $table->string('name');
            $table->string('original_name');
            $table->string('alt')->nullable();
            $table->string('mime_type')->index();
            $table->unsignedBigInteger('size');
            $table->json('metadata')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Where library items are used: model + collection, in order. Detaching
        // removes the row, never the file.
        Schema::create('mediables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('mediable');
            $table->string('collection');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['media_id', 'mediable_type', 'mediable_id', 'collection'], 'mediables_unique');
            $table->index(['mediable_type', 'mediable_id', 'collection'], 'mediables_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
        Schema::dropIfExists('media');
    }
};
