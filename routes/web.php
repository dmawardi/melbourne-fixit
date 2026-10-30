<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('llms.txt', [SeoController::class, 'llms'])->name('llms');

Route::middleware(['auth', 'verified'])->group(function () {
    // Customers land on their requests; kept for links to the starter kit's dashboard.
    Route::redirect('dashboard', 'requests')->name('dashboard');
});

require __DIR__.'/admin.php';
require __DIR__.'/job-requests.php';
require __DIR__.'/settings.php';
