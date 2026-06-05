<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info')->index();
            $table->string('audience')->default('students')->index();
            $table->string('trigger_event')->default('login')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('deliver_once')->default(true);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::table('user_notifications', function (Blueprint $table): void {
            $table->foreignId('notification_campaign_id')->nullable()->after('user_id')->constrained('notification_campaigns')->nullOnDelete();
        });

        DB::table('notification_campaigns')->insert([
            'title' => 'Welcome to your learning dashboard',
            'message' => 'Your account is ready. Browse courses, track orders, and access your ANME Academy learning updates from one place.',
            'type' => 'info',
            'audience' => 'students',
            'trigger_event' => 'login',
            'is_active' => true,
            'deliver_once' => true,
            'data' => json_encode(['system_default' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('notification_campaign_id');
        });

        Schema::dropIfExists('notification_campaigns');
    }
};
