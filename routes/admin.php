<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Access\UserController;
use App\Http\Controllers\Admin\Access\RoleController;
use App\Http\Controllers\Admin\Access\PermissionController;
use App\Http\Controllers\Admin\ContractTemplateController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Org\OrgController;

Route::middleware(['web', 'auth', 'team.scope', 'admin.access'])->group(function () {

    Route::get('admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::prefix('admin/settings/access')->name('admin.')->group(function () {
        Route::get('users',           [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::post('users',          [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('users/{id}',      [UserController::class, 'show'])->middleware('permission:users.view')->name('users.show');
        Route::put('users/{user}',    [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
        Route::get('roles/options',   [UserController::class, 'roleOptions'])->middleware('permission:users.view')->name('roles.options');

        Route::get('roles',           [RoleController::class, 'index'])->middleware('permission:rbac.view')->name('roles.index');
        Route::post('roles',          [RoleController::class, 'store'])->middleware('permission:rbac.assign')->name('roles.store');
        Route::put('roles/{role}',    [RoleController::class, 'update'])->middleware('permission:rbac.assign')->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:rbac.assign')->name('roles.destroy');

        Route::get('permissions',              [PermissionController::class, 'index'])->middleware('permission:rbac.view')->name('permissions.index');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:rbac.assign')->name('permissions.update');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('contract-templates', ContractTemplateController::class)
            ->middleware(['role:Superadmin|DHC']);
    });
    
    Route::prefix('admin/org')->name('admin.org.')->middleware('permission:org.view')->group(function () {
        Route::get('/', [OrgController::class, 'index'])->name('index');
        Route::get('tree', [OrgController::class, 'tree'])->name('tree');
        
        Route::get('directorates',            [OrgController::class, 'directorates'])->name('directorates.list');
        Route::get('directorates/options',    [OrgController::class, 'directorateOptions'])->name('directorates.options');
        Route::post('directorates',           [OrgController::class, 'directorateStore'])->middleware('permission:org.create')->name('directorates.store');
        Route::put('directorates/{id}',       [OrgController::class, 'directorateUpdate'])->middleware('permission:org.update')->name('directorates.update');
        Route::delete('directorates/{id}',    [OrgController::class, 'directorateDestroy'])->middleware('permission:org.delete')->name('directorates.destroy');

        Route::get('units',                   [OrgController::class, 'units'])->name('units.list');
        Route::post('units',                  [OrgController::class, 'unitStore'])->middleware('permission:org.create')->name('units.store');
        Route::put('units/{id}',              [OrgController::class, 'unitUpdate'])->middleware('permission:org.update')->name('units.update');
        Route::put('units/{id}/reassign',     [OrgController::class, 'unitReassign'])->middleware('permission:org.update')->name('units.reassign');
        Route::delete('units/{id}',           [OrgController::class, 'unitDestroy'])->middleware('permission:org.delete')->name('units.destroy');
    });
});