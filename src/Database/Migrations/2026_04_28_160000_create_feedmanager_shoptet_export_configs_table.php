<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedmanager_shoptet_export_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->string('access_token', 64)->unique();
            // Shoptet supports two import modes:
            //   full   — 1×/day, complete product description
            //   stock  — 1–16×/day, price + stock + visibility + availability only
            $table->string('feed_type', 16)->default('full')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('last_count')->nullable();
            $table->string('last_status', 16)->nullable();
            $table->text('last_message')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedmanager_shoptet_export_configs');
    }
};
