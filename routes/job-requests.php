<?php

use App\Http\Controllers\JobRequests\JobRequestController;
use App\Http\Controllers\JobRequests\JobRequestFileController;
use Illuminate\Support\Facades\Route;

// Public quote request form.
Route::get('request-a-quote', [JobRequestController::class, 'create'])->name('job-requests.create');
Route::post('request-a-quote', [JobRequestController::class, 'store'])
    ->middleware('throttle:job-requests')
    ->name('job-requests.store');
Route::get('request-a-quote/submitted', [JobRequestController::class, 'submitted'])->name('job-requests.submitted');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('requests', [JobRequestController::class, 'index'])->name('job-requests.index');
    Route::get('requests/{jobRequest}', [JobRequestController::class, 'show'])->name('job-requests.show');
    Route::patch('requests/{jobRequest}', [JobRequestController::class, 'update'])->name('job-requests.update');
    Route::delete('requests/{jobRequest}', [JobRequestController::class, 'destroy'])->name('job-requests.destroy');

    Route::scopeBindings()->group(function () {
        Route::post('requests/{jobRequest}/photos', [JobRequestFileController::class, 'store'])
            ->middleware('throttle:job-request-photos')
            ->name('job-requests.photos.store');
        Route::patch('requests/{jobRequest}/photos/{file}', [JobRequestFileController::class, 'update'])
            ->name('job-requests.photos.update');
        Route::delete('requests/{jobRequest}/photos/{file}', [JobRequestFileController::class, 'destroy'])
            ->name('job-requests.photos.destroy');
    });
});
