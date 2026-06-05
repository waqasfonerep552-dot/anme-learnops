<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('courses', 'moodle_shortname')) {
                $table->string('moodle_shortname')->nullable()->after('moodle_course_id')->index();
            }

            if (! Schema::hasColumn('courses', 'moodle_visible')) {
                $table->boolean('moodle_visible')->default(true)->after('moodle_shortname')->index();
            }

            if (! Schema::hasColumn('courses', 'moodle_format')) {
                $table->string('moodle_format')->nullable()->after('moodle_visible');
            }

            if (! Schema::hasColumn('courses', 'moodle_start_at')) {
                $table->timestamp('moodle_start_at')->nullable()->after('moodle_format');
            }

            if (! Schema::hasColumn('courses', 'moodle_end_at')) {
                $table->timestamp('moodle_end_at')->nullable()->after('moodle_start_at');
            }

            if (! Schema::hasColumn('courses', 'moodle_synced_at')) {
                $table->timestamp('moodle_synced_at')->nullable()->after('moodle_end_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $columns = [
                'moodle_shortname',
                'moodle_visible',
                'moodle_format',
                'moodle_start_at',
                'moodle_end_at',
                'moodle_synced_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
