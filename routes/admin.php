<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JobRequestController;
use App\Http\Controllers\Admin\JobRequestFileController;
use App\Http\Controllers\Admin\JobRequestMessageController;
use App\Http\Controllers\Admin\JobRequestVendorController;
use App\Http\Controllers\Admin\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('index');

        Route::get('requests', [JobRequestController::class, 'index'])->name('job-requests.index');
        Route::get('requests/{jobRequest}', [JobRequestController::class, 'show'])->name('job-requests.show');
        Route::patch('requests/{jobRequest}', [JobRequestController::class, 'update'])->name('job-requests.update');
        Route::delete('requests/{jobRequest}', [JobRequestController::class, 'destroy'])->name('job-requests.destroy');

        Route::put('requests/{jobRequest}/vendor', [JobRequestVendorController::class, 'update'])
            ->name('job-requests.vendor.update');
        Route::delete('requests/{jobRequest}/vendor', [JobRequestVendorController::class, 'destroy'])
            ->name('job-requests.vendor.destroy');

        Route::resource('vendors', VendorController::class)->except('show');

        Route::scopeBindings()->group(function () {
            Route::post('requests/{jobRequest}/files', [JobRequestFileController::class, 'store'])
                ->name('job-requests.files.store');
            Route::patch('requests/{jobRequest}/files/{file}', [JobRequestFileController::class, 'update'])
                ->name('job-requests.files.update');
            Route::delete('requests/{jobRequest}/files/{file}', [JobRequestFileController::class, 'destroy'])
                ->name('job-requests.files.destroy');

            Route::post('requests/{jobRequest}/messages', [JobRequestMessageController::class, 'store'])
                ->name('job-requests.messages.store');
            Route::delete('requests/{jobRequest}/messages/{message}', [JobRequestMessageController::class, 'destroy'])
                ->name('job-requests.messages.destroy');
        });
    });
