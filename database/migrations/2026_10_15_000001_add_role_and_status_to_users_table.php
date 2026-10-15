<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds what DDC Workforce needs on top of Laravel's default users table:
 *  - role:          hr / head / employee (see App\Enums\Role)
 *  - is_active:     a deactivated account cannot sign in
 *  - last_login_at: shown to HR on the users list
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('employee')->after('password')->index();
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });
    }
};
