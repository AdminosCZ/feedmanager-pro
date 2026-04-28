<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedmanager_partners', function (Blueprint $table) {
            // Partner-level defaults for stock-visibility behaviour. Different
            // tiers (standard / VIP) get different thresholds so admin can
            // protect inventory more aggressively for low-margin partners.
            $table->unsignedInteger('default_low_stock_threshold')->default(5)->after('feed_stock_limit');
            $table->string('default_low_stock_availability', 64)->default('Na dotaz')->after('default_low_stock_threshold');
            $table->string('default_out_of_stock_availability', 64)->default('Vyprodáno')->after('default_low_stock_availability');
        });
    }

    public function down(): void
    {
        Schema::table('feedmanager_partners', function (Blueprint $table) {
            $table->dropColumn([
                'default_low_stock_threshold',
                'default_low_stock_availability',
                'default_out_of_stock_availability',
            ]);
        });
    }
};
