<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ProfileController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/





Route::get('/', function () {
    return view('welcome');
});



Route::get('reset-pin', [ProfileController::class, 'reset_pin']);
Route::get('reset-password', [ProfileController::class, 'reset_password']);


Route::post('reset-pin-now', [ProfileController::class, 'reset_pin_now']);
Route::post('reset-password-now', [ProfileController::class, 'reset_password_now']);


Route::get('success', [ProfileController::class, 'success']);




