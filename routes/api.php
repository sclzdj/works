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

//前台
$api=app(\Dingo\Api\Routing\Router::class);
#默认指定的是v1版本和前缀方式，则直接通过 {host}/{前缀}/{接口名} 访问即可
$api->version('v1',['namespace'=>'\App\Http\Controllers\Api'],function ($api){
    #api.throttle中间件是限制请求次数 每expires分钟只能请求limit次
    $api->group(['middleware'=>'api.throttle','limit'=>1000,'expires'=>1],function($api){
        $api->get('login', 'LoginController@mpLogin');
        $api->post('login', 'LoginController@login');
        $api->get('my/logout', 'MyController@logout');
        $api->get('my/refresh', 'MyController@refresh');
        $api->post('my/saveInfo', 'MyController@saveInfo');
        $api->get('my/info', 'MyController@info');
    });
});

#默认指定的不是v2版本，需要先设置请求头 #Accept:application/[配置项standardsTrss].[配置项subtype].v2+json，再通过 {host}/{前缀}/{接口名} 访问
$api->version('v2',function ($api){
    $api->get('version',function(){
        return "v2";
    });
});
