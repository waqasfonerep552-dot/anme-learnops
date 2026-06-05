<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->string('country_code', 10)->nullable()->after('ip_address')->index();
            $table->string('country_name')->nullable()->after('country_code');
            $table->string('city')->nullable()->after('country_name');
            $table->string('device_type', 40)->nullable()->after('user_agent')->index();
            $table->string('browser', 80)->nullable()->after('device_type');
            $table->string('platform', 80)->nullable()->after('browser');
            $table->string('http_method', 10)->nullable()->after('platform');
            $table->string('route_name')->nullable()->after('http_method')->index();
            $table->text('url')->nullable()->after('route_name');
            $table->text('referer')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropColumn([
                'country_code',
                'country_name',
                'city',
                'device_type',
                'browser',
                'platform',
                'http_method',
                'route_name',
                'url',
                'referer',
            ]);
        });
    }
};
