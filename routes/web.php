<?php

use App\Http\Controllers\LinesController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\OperatorsController;
use Illuminate\Support\Facades\Route;

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
    return view('index');
});


Route::get('/sms', function () {
    return "hello world";
});


Route::group([ 'prefix' => 'lines' ], function () {
    Route::get('/', [LinesController::class, 'index']);
});
Route::group([ 'prefix' => 'operators', 'as' => 'operator.' ], function () {
    Route::get('/', [OperatorsController::class, 'index']);
});
Route::group([ 'prefix' => 'messages', 'as' => 'message.' ], function () {
    Route::get('/', [MessagesController::class, 'index']);
});