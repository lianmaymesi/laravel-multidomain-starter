<?php

use Atrium\Core\Http\Controllers\Backoffice\BackupDownloadController;
use Atrium\Core\Support\Modules\Module;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::backoffice.dashboard')->name('dashboard');
Route::livewire('roles', 'pages::backoffice.roles')->name('roles.index');
Route::livewire('roles/{role}/permissions', 'pages::backoffice.roles.permissions')->name('roles.permissions');
Route::livewire('permissions', 'pages::backoffice.permissions')->name('permissions.index');
Route::livewire('users', 'pages::backoffice.users')->name('users.index');
Route::livewire('settings', 'pages::backoffice.settings')->name('settings.index');
Route::livewire('modules', 'pages::backoffice.modules')->name('modules.index');
Route::livewire('feature-flags', 'pages::backoffice.feature-flags')->name('feature-flags.index');
// Not "health": /health is the readiness endpoint on every host.
Route::livewire('system-health', 'pages::backoffice.health')->name('health.index');
Route::livewire('backups', 'pages::backoffice.backups')->name('backups.index');
Route::get('backups/download', BackupDownloadController::class)->name('backups.download');

// Routes contributed by enabled feature modules (app/Modules/*/routes/backoffice.php).
Module::routes('backoffice');
