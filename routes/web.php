<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\PageController::class, 'index'])->name('home');

    Route::resource('user', \App\Http\Controllers\UserController::class)
        ->except(['show', 'edit', 'create'])
        ->middleware(['role:admin']);

    Route::get('profile', [\App\Http\Controllers\PageController::class, 'profile'])
        ->name('profile.show');
    Route::put('profile', [\App\Http\Controllers\PageController::class, 'profileUpdate'])
        ->name('profile.update');
    Route::put('profile/deactivate', [\App\Http\Controllers\PageController::class, 'deactivate'])
        ->name('profile.deactivate')
        ->middleware(['role:staff']);

    Route::get('settings', [\App\Http\Controllers\PageController::class, 'settings'])
        ->name('settings.show')
        ->middleware(['role:admin']);
    Route::put('settings', [\App\Http\Controllers\PageController::class, 'settingsUpdate'])
        ->name('settings.update')
        ->middleware(['role:admin']);

    Route::delete('attachment', [\App\Http\Controllers\PageController::class, 'removeAttachment'])
        ->name('attachment.destroy');

    Route::prefix('transaction')->as('transaction.')->group(function () {
        Route::resource('incoming', \App\Http\Controllers\IncomingLetterController::class);
        Route::resource('outgoing', \App\Http\Controllers\OutgoingLetterController::class);
        Route::resource('{letter}/disposition', \App\Http\Controllers\DispositionController::class)->except(['show']);
    });

    Route::prefix('agenda')->as('agenda.')->group(function () {
        Route::get('incoming', [\App\Http\Controllers\IncomingLetterController::class, 'agenda'])->name('incoming');
        Route::get('incoming/print', [\App\Http\Controllers\IncomingLetterController::class, 'print'])->name('incoming.print');
        Route::get('outgoing', [\App\Http\Controllers\OutgoingLetterController::class, 'agenda'])->name('outgoing');
        Route::get('outgoing/print', [\App\Http\Controllers\OutgoingLetterController::class, 'print'])->name('outgoing.print');
    });

    Route::prefix('gallery')->as('gallery.')->group(function () {
        Route::get('incoming', [\App\Http\Controllers\LetterGalleryController::class, 'incoming'])->name('incoming');
        Route::get('outgoing', [\App\Http\Controllers\LetterGalleryController::class, 'outgoing'])->name('outgoing');
    });

    Route::prefix('reference')->as('reference.')->middleware(['role:admin'])->group(function () {
        Route::resource('classification', \App\Http\Controllers\ClassificationController::class)->except(['show', 'create', 'edit']);
        Route::resource('status', \App\Http\Controllers\LetterStatusController::class)->except(['show', 'create', 'edit']);
    });

    // OneDrive Management Routes
    Route::prefix('onedrive')->as('onedrive.')->group(function () {
        // OneDrive file management (requires connection)
        Route::middleware(['onedrive.connected'])->group(function () {
            Route::get('/', [\App\Http\Controllers\OneDriveController::class, 'index'])->name('index');
            Route::get('/files', [\App\Http\Controllers\OneDriveController::class, 'listFiles'])->name('files');
            Route::post('/upload', [\App\Http\Controllers\OneDriveController::class, 'upload'])->name('upload');
            Route::delete('/file', [\App\Http\Controllers\OneDriveController::class, 'deleteFile'])->name('delete');
            Route::post('/folder', [\App\Http\Controllers\OneDriveController::class, 'createFolder'])->name('folder.create');
            Route::get('/sync', [\App\Http\Controllers\OneDriveController::class, 'sync'])->name('sync');
            Route::get('/stats', [\App\Http\Controllers\OneDriveController::class, 'storageStats'])->name('stats');
        });
        
        // OneDrive authentication (admin only)
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/auth', [\App\Http\Controllers\OneDriveAuthController::class, 'authenticate'])->name('auth');
            Route::post('/disconnect', [\App\Http\Controllers\OneDriveAuthController::class, 'disconnect'])->name('disconnect');
            Route::get('/status', [\App\Http\Controllers\OneDriveAuthController::class, 'status'])->name('status');
            Route::post('/refresh', [\App\Http\Controllers\OneDriveAuthController::class, 'refreshToken'])->name('refresh');
        });
    });

});

// OneDrive OAuth callback (outside auth middleware for Microsoft to access)
Route::get('/auth/onedrive/callback', [\App\Http\Controllers\OneDriveAuthController::class, 'callback'])
    ->name('onedrive.auth.callback');
