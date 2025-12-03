<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address', 45);
            $table->unsignedInteger('request_count')->default(1);
            $table->timestamp('first_request_at');
            $table->timestamp('last_request_at');
            $table->timestamps();

            // Indexes
            $table->index('ip_address');
            $table->index('last_request_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_requests');
    }
};
