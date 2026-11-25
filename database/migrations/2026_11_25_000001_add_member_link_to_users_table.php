<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // A member-portal login belongs to exactly one member record (staff accounts leave this null).
            $table->foreignId('member_id')->nullable()->unique()->after('is_active')
                ->constrained('members')->cascadeOnDelete();
            // Set when the member has chosen their own password from the invitation link.
            $table->timestamp('portal_activated_at')->nullable()->after('member_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
            $table->dropColumn('portal_activated_at');
        });
    }
};
