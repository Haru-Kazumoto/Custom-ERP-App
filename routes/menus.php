<?php

use App\Modules\Menus\Controllers\MenusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->name('menus.')
    ->prefix('menus')
    ->group(function () {
        Route::get('/', [MenusController::class, 'index'])->name('index');
        Route::get('/create', [MenusController::class, 'create'])->name('create');
        Route::post('/store', [MenusController::class, 'store'])->name('store');
        Route::get('/manage-menu', [MenusController::class, 'manageMenu'])->name('manage-menu');
        Route::post('/attach-parent', [MenusController::class, 'attachToParent'])->name('attach-parent');
        Route::post('/detach-parent', [MenusController::class, 'detachFromParent'])->name('detach-parent');
        Route::get('/manage-role', [MenusController::class, 'manageRole'])->name('manage-role');
        Route::post('/sync-role-menus', [MenusController::class, 'syncRoleMenus'])->name('sync-role-menus');
    });
