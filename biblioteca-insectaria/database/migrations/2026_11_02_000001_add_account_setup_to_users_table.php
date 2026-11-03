<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->boolean('must_change_password')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        if (! DB::table('users')->whereNull('email')->exists()) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable(false)->change();
            });
        }
    }
};
