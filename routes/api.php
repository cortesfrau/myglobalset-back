<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CollectedCardPrintController;

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

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);

    Route::post('send-password-reset-link', [ResetPasswordController::class, 'sendEmail']);
    Route::post('change-password', [ResetPasswordController::class, 'processPasswordChange']);

});


Route::group([

    'middleware' => 'api',
    'prefix' => 'user'

], function ($router) {

    Route::post('update', [UserController::class, 'update']);

});


Route::group([

    'middleware' => 'api',
    'prefix' => 'collection'

], function ($router) {

    Route::post('create', [CollectionController::class, 'create']);
    Route::get('{collectionId}', [CollectionController::class, 'getCollection']);
    Route::get('user/{userId}', [CollectionController::class, 'getUserCollections']);

});


Route::group([

    'middleware' => 'api',
    'prefix' => 'collected-card-print'

], function ($router) {

    Route::post('create', [CollectedCardPrintController::class, 'create']);
    Route::post('remove', [CollectedCardPrintController::class, 'remove']);
    Route::get('is-print-in-collection', [CollectedCardPrintController::class, 'isPrintInCollection']);

});
