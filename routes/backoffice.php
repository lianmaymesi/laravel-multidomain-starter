<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::backoffice.dashboard')->name('dashboard');
Route::livewire('roles', 'pages::backoffice.roles')->name('roles.index');
Route::livewire('permissions', 'pages::backoffice.permissions')->name('permissions.index');
Route::livewire('users', 'pages::backoffice.users')->name('users.index');
Route::livewire('maintenance', 'pages::backoffice.maintenance')->name('maintenance.index');
