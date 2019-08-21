<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


//后台
Route::group(['prefix' => 'admin', 'namespace' => 'Admin'], function () {
    Route::any('system/file/upload', 'System\FileController@upload');
    Route::any('system/file/ueditorUploadConfig', 'System\FileController@ueditorUploadConfig');
    Route::any('system/file/ueditorList', 'System\FileController@ueditorList');
    Route::any('system/file/ueditorCatchImage', 'System\FileController@ueditorCatchImage');
});

