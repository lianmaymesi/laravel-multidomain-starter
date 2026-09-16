<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->boolean('locked')->default(false)->after('slug');
        });

        $subDomains = config('multidomain.sub_domains', []);

        foreach (DB::table('roles')->get() as $role) {
            if ($role->name === 'Super Admin') {
                DB::table('roles')->where('id', $role->id)->update([
                    'slug' => 'super-admin',
                    'locked' => true,
                ]);

                continue;
            }

            if (strcasecmp($role->name, 'Admin') === 0) {
                DB::table('roles')->where('id', $role->id)->update([
                    'slug' => 'admin',
                    'locked' => true,
                ]);

                continue;
            }

            if (array_key_exists($role->name, $subDomains)) {
                DB::table('roles')->where('id', $role->id)->update([
                    'slug' => $role->name,
                    'name' => Str::headline($role->name),
                ]);

                continue;
            }

            DB::table('roles')->where('id', $role->id)->update([
                'slug' => Str::slug($role->name),
            ]);
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique(['slug', 'guard_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['slug', 'guard_name']);
            $table->dropColumn(['slug', 'locked']);
        });
    }
};
