<?php

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('academy_username')->nullable()->after('moodle_user_id')->unique();
            $table->timestamp('academy_password_set_at')->nullable()->after('academy_username');
            $table->timestamp('academy_setup_required_at')->nullable()->after('academy_password_set_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['academy_username', 'academy_password_set_at', 'academy_setup_required_at']);
        });
    }
};
