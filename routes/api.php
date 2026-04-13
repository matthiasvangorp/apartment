<?php

use App\Http\Controllers\Api\ApplianceController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\UtilityController;
use Illuminate\Support\Facades\Route;

Route::middleware('apartment.token')->prefix('v1')->group(function () {
    Route::get('utility/summary', [UtilityController::class, 'summary']);
    Route::get('utility/readings', [UtilityController::class, 'readings']);

    Route::get('maintenance/upcoming', [MaintenanceController::class, 'upcoming']);

    Route::get('appliances', [ApplianceController::class, 'index']);

    Route::get('documents/search', [DocumentController::class, 'search']);
    Route::get('documents/{document}', [DocumentController::class, 'show']);
});

Route::get('v1/documents/{document}/download', [DocumentController::class, 'download'])
    ->name('apartment.documents.download');
