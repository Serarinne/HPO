<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\FetchController;
use App\Http\Controllers\FetchedWallpaperController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RedeemController;
use App\Http\Controllers\SEOController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\WallpaperController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login/firebase', 'firebaseLogin')->name('login.firebase');
});

Route::middleware(['auth', IsAdmin::class])->group(function () {
    // Dashboard & Settings
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');

    // Wallpapers
    Route::prefix('wallpapers')->name('wallpapers.')->group(function () {
        Route::get('/', [WallpaperController::class, 'index'])->name('index');
        Route::get('/{id}', [WallpaperController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [WallpaperController::class, 'update'])->name('update');
        Route::delete('/{id}', [WallpaperController::class, 'delete'])->name('delete');
        Route::post('/{id}/reupload', [WallpaperController::class, 'reupload'])->name('reupload');
        Route::patch('/{id}/toggle-debug', [WallpaperController::class, 'toggleDebug'])->name('toggle-debug');
        Route::patch('/{id}/update-rating', [WallpaperController::class, 'updateRating'])->name('update-rating');
    });

    // Characters
    Route::prefix('characters')->name('characters.')->group(function () {
        Route::get('/', [CharacterController::class, 'index'])->name('index');
        Route::get('/list', [CharacterController::class, 'list'])->name('list');
        Route::delete('/bulk-delete', [CharacterController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('/{id}', [CharacterController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [CharacterController::class, 'update'])->name('update');
        Route::delete('/{id}', [CharacterController::class, 'delete'])->name('delete');
        Route::patch('/{id}/toggle-debug', [CharacterController::class, 'toggleDebug'])->name('toggle-debug');
        Route::patch('/{id}/update-rating', [CharacterController::class, 'updateRating'])->name('update-rating');
        Route::get('/{id}/merge', [CharacterController::class, 'mergeForm'])->name('merge.form');
        Route::post('/{id}/merge', [CharacterController::class, 'merge'])->name('merge');
    });

    // Series
    Route::prefix('series')->name('series.')->group(function () {
        Route::get('/', [SeriesController::class, 'index'])->name('index');
        Route::get('/list', [SeriesController::class, 'list'])->name('list');
        Route::get('/{id}', [SeriesController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [SeriesController::class, 'update'])->name('update');
        Route::delete('/{id}', [SeriesController::class, 'delete'])->name('delete');
        Route::patch('/{id}/toggle-debug', [SeriesController::class, 'toggleDebug'])->name('toggle-debug');
        Route::patch('/{id}/update-rating', [SeriesController::class, 'updateRating'])->name('update-rating');
    });

    // Tags
    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [TagController::class, 'index'])->name('index');
        Route::get('/list', [TagController::class, 'list'])->name('list');
        Route::get('/{id}', [TagController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [TagController::class, 'update'])->name('update');
        Route::delete('/{id}', [TagController::class, 'delete'])->name('delete');
        Route::patch('/{id}/toggle-debug', [TagController::class, 'toggleDebug'])->name('toggle-debug');
        Route::patch('/{id}/update-rating', [TagController::class, 'updateRating'])->name('update-rating');
    });

    // Artists
    Route::prefix('artists')->name('artists.')->group(function () {
        Route::get('/', [ArtistController::class, 'index'])->name('index');
        Route::get('/list', [ArtistController::class, 'list'])->name('list');
        Route::get('/{id}', [ArtistController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [ArtistController::class, 'update'])->name('update');
        Route::delete('/{id}', [ArtistController::class, 'delete'])->name('delete');
        Route::patch('/{id}/toggle-debug', [ArtistController::class, 'toggleDebug'])->name('toggle-debug');
    });

    // Posts
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/create', [PostController::class, 'create'])->name('create');
        Route::post('/create', [PostController::class, 'store'])->name('store');
        Route::post('/upload-content-image', [PostController::class, 'uploadContentImage'])->name('upload-content-image');
        Route::post('/editor-image-upload', [PostController::class, 'uploadEditorImage'])->name('editor-image-upload');
        Route::post('/ajax-store', [PostController::class, 'ajaxStore'])->name('ajax-store');
        Route::get('/{id}', [PostController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [PostController::class, 'update'])->name('update');
        Route::delete('/{id}', [PostController::class, 'delete'])->name('delete');
    });

    // Redeems
    Route::prefix('redeems')->name('redeems.')->group(function () {
        Route::get('/', [RedeemController::class, 'index'])->name('index');
        Route::get('/create', [RedeemController::class, 'create'])->name('create');
        Route::post('/create', [RedeemController::class, 'store'])->name('store');
        Route::get('/{id}', [RedeemController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [RedeemController::class, 'update'])->name('update');
        Route::delete('/{id}', [RedeemController::class, 'delete'])->name('delete');
    });

    // Fetch Tasks
    Route::prefix('fetch-tasks')->name('fetch-tasks.')->group(function () {
        Route::get('/', [FetchController::class, 'index'])->name('index');
        Route::post('/', [FetchController::class, 'store'])->name('store');
        Route::post('/populate', [FetchController::class, 'populate'])->name('populate');
        Route::post('/{id}/reset', [FetchController::class, 'reset'])->name('reset');
        Route::delete('/{id}', [FetchController::class, 'destroy'])->name('destroy');
    });

    // Fetched Wallpapers
    Route::prefix('fetch-wallpapers')->name('fetch-wallpapers.')->group(function () {
        Route::get('/', [FetchedWallpaperController::class, 'index'])->name('index');
        Route::post('/{id}/approve', [FetchedWallpaperController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [FetchedWallpaperController::class, 'reject'])->name('reject');
        Route::post('/{id}/replace/{target_id}', [FetchedWallpaperController::class, 'replace'])->name('replace');
        Route::delete('/{id}', [FetchedWallpaperController::class, 'destroy'])->name('destroy');
    });

    // SEO
    Route::prefix('seo')->name('seo.')->group(function () {
        Route::get('/wallpaper/{id}', [SEOController::class, 'generateWallpaperPrompt'])->name('wallpaper');
        Route::get('/character/{id}', [SEOController::class, 'generateCharacterPrompt'])->name('character');
        Route::get('/series/{id}', [SEOController::class, 'generateSeriesPrompt'])->name('series');
        Route::get('/tags/{id}', [SEOController::class, 'generateTagPrompt'])->name('tags');
        Route::get('/artists/{id}', [SEOController::class, 'generateArtistPrompt'])->name('artists');
    });
});