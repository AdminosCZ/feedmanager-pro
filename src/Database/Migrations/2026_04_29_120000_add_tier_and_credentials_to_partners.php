<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedmanager_partners', function (Blueprint $table) {
            // Standard / VIP — drives default thresholds. Will eventually be
            // (re-)assigned automatically based on rolling revenue, for now
            // it's a manual tag.
            $table->string('tier', 16)->default('standard')->after('zip');

            // HTTP Basic Auth credentials so the feed URL alone (e.g. leaked
            // from server access logs) isn't enough to download the catalogue.
            // Password is encrypted via APP_KEY (TEXT — encrypted ciphertext is
            // ~4–5× longer than plaintext).
            $table->string('feed_username', 64)->nullable()->after('access_token');
            $table->text('feed_password')->nullable()->after('feed_username');
        });

        // Backfill credentials for partners that already exist. Without this,
        // the middleware would have to allow unauthenticated access for legacy
        // rows — defeating the whole point of adding the column.
        DB::table('feedmanager_partners')
            ->whereNull('feed_password')
            ->orderBy('id')
            ->each(function (object $row): void {
                DB::table('feedmanager_partners')
                    ->where('id', $row->id)
                    ->update([
                        'feed_username' => 'partner-'.Str::lower(Str::random(8)),
                        'feed_password' => Crypt::encryptString(Str::random(24)),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('feedmanager_partners', function (Blueprint $table) {
            $table->dropColumn(['tier', 'feed_username', 'feed_password']);
        });
    }
};
