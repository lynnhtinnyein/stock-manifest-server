<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// used middlewares list
// 'auth:sanctum', //to authenticate user
// 'check.suspended', // to check if user is suspended
// 'extend.token.lifetime', to auto extend token lifetime on each request
// 'check.role.access', // to check if user can access target user based on role hierarchy
// 'permission' // to check if user has required permission

Route::prefix('v1')->group(function () {

    // Route::prefix('public')->controller(PublicController::class)->group(function () {
    //     Route::get('/e-certificates/{public_id}', 'showEcertificateByPublicId');
    //     Route::get('/e-certificates/{public_id}/download', 'downloadEcertificateByPublicId'); // allowed to download only issued certificates
    // });

    // public auth
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('/login', 'login');
        // not in used
        // Route::post('/register', 'register');
    });

    Route::middleware(['auth:sanctum', 'check.suspended', 'extend.token.lifetime'])->group(function () {
        // Auth
        Route::prefix('auth')->controller(AuthController::class)->group(function () {
            Route::get('/me', 'me');
            Route::get('/check', 'check');
            Route::post('/logout', 'logout');
            Route::patch('/change-password', 'changePassword');
        });

        // Permissions
        Route::middleware(['permission:users.manage'])
            ->group(function () {
                Route::get('/permissions', [PermissionController::class, 'index']);
            });

        // Users
        Route::prefix('users')->controller(UserController::class)->group(function () {
            // Create
            Route::middleware(['permission:users.manage', 'check.role.access'])->group(function () {
                Route::post('/', 'store');
            });

            // View
            Route::middleware('permission:users.view')->group(function () {
                Route::get('/', 'index');
                Route::get('/trashed', 'trashed');
                Route::get('/{id}', 'show')->middleware('check.role.access');
            });

            // Update
            Route::middleware(['permission:users.manage', 'check.role.access'])->group(function () {
                Route::put('/{user}', 'update');
                Route::patch('/{user}/update-password', 'updatePassword');

                // Delete / Restore from Trash / Force Delete
                Route::delete('/{id}', 'destroy');
                Route::patch('/{id}/restore-from-trash', 'restoreFromTrash');
                Route::delete('/{id}/force', 'forceDelete');

                // Suspend / Restore
                Route::patch('/{user}/suspend', 'suspend');
                Route::patch('/{user}/restore', 'restore');
            });
        });

        // Categories
        Route::prefix('categories')->middleware('permission:items.default')->controller(CategoryController::class)->group(function () {
            Route::apiResource('/', CategoryController::class);
            Route::post('/{id}/restore', [CategoryController::class, 'restore']);
        });

        // Items
        Route::prefix('items')->middleware('permission:items.default')->controller(ItemController::class)->group(function () {
            Route::apiResource('/', ItemController::class);
            Route::post('/{item}/image', [ItemController::class, 'syncImage']);
            Route::post('/{id}/restore', [ItemController::class, 'restore']);
        });

        // Settings
        Route::prefix('settings')->middleware('permission:items.default')->controller(SettingController::class)->group(function () {
            Route::get('/', 'index');
            Route::put('/', 'update');
        });
    });
});
