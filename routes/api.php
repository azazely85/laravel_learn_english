<?php


use App\Http\Controllers\Api\LearningController;
use App\Http\Controllers\Api\WordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
|
|   Comment to check
|   Comment to check 2
*/


// auth routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forget-password', [AuthController::class, 'sendResetLink']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::group(['middleware' => 'auth:sanctum'], function() {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::group(['middleware' => ['admin']], function() {
            Route::get('users', [UserController::class, 'users']);
            Route::delete('user/{id}', [UserController::class, 'destroy']);
        });
    });
});
Route::get('word', [WordController::class, 'getWord']);
Route::get('world/voice/{file_name}', [WordController::class, 'voice']);
// api auth routes
Route::group(['middleware' => ['auth:sanctum']], function() {

    Route::group(['middleware' => ['admin']], function() {
        Route::get('user_words', [WordController::class, 'getUserWords']);

        Route::get('learning/translate_words', [LearningController::class, 'getTranslateWord']);
        Route::get('learning/words_translate', [LearningController::class, 'getWordTranslate']);
        Route::get('learning/audio_words', [LearningController::class, 'getWordAudio']);
        Route::get('learning/change_status', [LearningController::class, 'changeStatus']);
        Route::get('learning/change_status_id', [LearningController::class, 'changeStatusId']);
        Route::get('learning/change_repeat', [LearningController::class, 'changeRepeat']);
        Route::get('learning/count', [LearningController::class, 'count']);
        Route::get('learning/get_repeat', [LearningController::class, 'getRepeat']);
        Route::delete('user/{id}', [UserController::class, 'destroy']);
    });
});
