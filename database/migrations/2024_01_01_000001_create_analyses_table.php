<?php

declare(strict_types=1);

use App\Enums\AnalysisStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('github_username', 39);
            $table->string('status', 20)->default(AnalysisStatus::PENDING->value);

            // Scores
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->unsignedTinyInteger('profile_score')->nullable();
            $table->unsignedTinyInteger('projects_score')->nullable();
            $table->unsignedTinyInteger('consistency_score')->nullable();
            $table->unsignedTinyInteger('technical_score')->nullable();
            $table->unsignedTinyInteger('community_score')->nullable();

            // JSON data
            $table->json('github_data')->nullable();
            $table->json('ai_analysis')->nullable();

            // Payment
            $table->boolean('is_paid')->default(false);
            $table->string('stripe_payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Tracking
            $table->string('ip_address', 45)->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('github_username');
            $table->index('status');
            $table->index('is_paid');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
