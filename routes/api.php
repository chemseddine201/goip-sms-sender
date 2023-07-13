<?php

use App\Http\Controllers\LinesController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\OperatorsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SMSController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('send-sms', [SMSController::class, 'store']);
Route::post('send-multi-sms', [SMSController::class, 'storeMulti']);
Route::delete('sms', [SMSController::class, 'destroy']);// delete single and multiple

Route::post('switch', [OperatorsController::class, 'update']);//open/close operator
Route::post('switch-all', [OperatorsController::class, 'switchAll']);//open/close all operators

Route::post('lines/switch', [LinesController::class, 'switch']);
Route::post('lines/reset', [LinesController::class, 'reset']);
Route::get('lines/freelongBusy', [LinesController::class, 'freeLongBusy']);

Route::post('operators/switch', [OperatorsController::class, 'switch']);

Route::delete('messages', [MessagesController::class, 'destroy']);
Route::post('messages/deleteAll', [MessagesController::class, 'deleteAll']);
Route::get('messages/fetch', [MessagesController::class, 'fetchData']);

