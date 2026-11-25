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
            // Least privilege by default for any account created later.
            $table->string('role', 30)->default('viewer')->after('password')->index();
            $table->boolean('is_active')->default(true)->after('role');
        });

        // Accounts that already exist were all full-access before RBAC: keep them as administrators
        // so nobody is locked out. Downgrade them afterwards from Users > Edit.
        DB::table('users')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
