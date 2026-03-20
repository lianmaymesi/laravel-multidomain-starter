<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login');
Route::livewire('login', 'pages::auth.login')->name('login');
Route::livewire('register', 'pages::auth.register')->name('register');
