<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedmanager_download_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partner_id')
                ->nullable()
                ->constrained('feedmanager_partners')
                ->nullOnDelete();

            $table->string('feed_type', 16); // full | stock
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('product_count')->nullable();

            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['partner_id', 'created_at']);
            $table->index(['partner_id', 'feed_type', 'status_code', 'created_at'], 'fmlogs_rate_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedmanager_download_logs');
    }
};
