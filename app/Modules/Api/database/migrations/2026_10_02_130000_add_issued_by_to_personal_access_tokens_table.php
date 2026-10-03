<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Who created the token: the owner (self-service) or an admin. Self-issued
    // tokens follow the access policy; admin-issued ones don't.
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->foreignId('issued_by')->nullable()->after('tokenable_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issued_by');
        });
    }
};
