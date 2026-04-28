<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedmanager_partners', function (Blueprint $table) {
            $table->id();

            $table->string('company_name');
            $table->string('ico', 16)->nullable();
            $table->string('dic', 16)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('zip', 16)->nullable();

            $table->uuid('access_token')->unique();
            $table->boolean('feeds_active')->default(true);
            $table->unsignedInteger('feed_full_limit')->default(10);
            $table->unsignedInteger('feed_stock_limit')->default(50);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedmanager_partners');
    }
};
