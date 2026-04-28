<?php

declare(strict_types=1);

use Adminos\Modules\FeedmanagerPro\Http\Controllers\B2bFeedController;
use Adminos\Modules\FeedmanagerPro\Http\Middleware\PartnerRateLimit;
use Adminos\Modules\FeedmanagerPro\Http\Middleware\PartnerTokenAuth;
use Illuminate\Support\Facades\Route;

// B2B partnerský feed — path-based pattern.
//   /feed/{token}/full
//   /feed/{token}/stock
Route::middleware([PartnerTokenAuth::class, PartnerRateLimit::class])
    ->get('/feed/{token}/{type}', [B2bFeedController::class, 'show'])
    ->name('feedmanager.b2b.feed')
    ->where('token', '[0-9a-fA-F-]+')
    ->where('type', 'full|stock');
