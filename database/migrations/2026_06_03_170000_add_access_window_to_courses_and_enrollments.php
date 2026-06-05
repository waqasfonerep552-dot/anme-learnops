<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->unsignedInteger('access_duration_days')
                ->default(0)
                ->after('duration')
                ->comment('0 means lifetime access; otherwise Moodle enrolment timeend is calculated from activation date.');
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->timestamp('access_starts_at')->nullable()->after('enrolled_at');
            $table->timestamp('access_ends_at')->nullable()->after('access_starts_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropColumn(['access_starts_at', 'access_ends_at']);
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn('access_duration_days');
        });
    }
};
