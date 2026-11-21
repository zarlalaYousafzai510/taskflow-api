<?php

use Illuminate\Support\Facades\Route;

Route::view('/docs', 'docs');

Route::get('/', function () {
    return view('welcome');
});
