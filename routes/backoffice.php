<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::backoffice.dashboard')->name('dashboard');
Route::livewire('roles', 'pages::backoffice.roles')->name('roles.index');
Route::livewire('roles/{role}/permissions', 'pages::backoffice.roles.permissions')->name('roles.permissions');
Route::livewire('permissions', 'pages::backoffice.permissions')->name('permissions.index');
Route::livewire('users', 'pages::backoffice.users')->name('users.index');
Route::livewire('maintenance', 'pages::backoffice.maintenance')->name('maintenance.index');
Route::livewire('activity', 'pages::backoffice.activity')->name('activity.index');
Route::livewire('languages', 'pages::backoffice.languages')->name('languages.index');
Route::livewire('translations/{scope?}', 'pages::backoffice.translations')->name('translations.index');
Route::livewire('settings', 'pages::backoffice.settings')->name('settings.index');
