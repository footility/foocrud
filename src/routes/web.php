<?php

use Footility\Foocrud\Http\Controllers\FooCrudController;

Route::prefix('foo/crud')->group(function () {
    Route::get('/', [FooCrudController::class, 'index'])->name('foocrud.dashboard');
    Route::post('/create-entity', [FooCrudController::class, 'createEntity'])->name('foocrud.createEntity');
    Route::post('/add-field/{entityId}', [FooCrudController::class, 'addField'])->name('foocrud.addField');
    Route::post('/generate-files', [FooCrudController::class, 'generateFiles'])->name('foocrud.generateFiles');
});
