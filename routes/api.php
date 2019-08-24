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
        //系统通用
        $api->post('sendSmsCode', 'SystemController@sendSmsCode');
        //用户登录
        $api->get('login', 'LoginController@mpLogin');
        $api->post('login', 'LoginController@login');
        //用户中心
        $api->get('my/logout', 'MyController@logout');
        $api->get('my/refresh', 'MyController@refresh');
        $api->post('my/saveInfo', 'MyController@saveInfo');
        $api->get('my/info', 'MyController@info');
        $api->post('my/photographerInfo', 'MyController@savePhotographerInfo');
        $api->post('my/photographerAvatar', 'MyController@savePhotographerAvatar');
        $api->post('my/photographerBgImg', 'MyController@savePhotographerBgImg');
        $api->get('my/photographerInfo', 'MyController@photographerInfo');
        $api->get('my/photographerWorks', 'MyController@photographerWorks');
        $api->get('my/photographerWork', 'MyController@photographerWork');
        $api->get('my/identity', 'MyController@identity');
        //摄影师注册
        $api->get('draft/registerPhotographerWorkImg', 'DraftController@registerPhotographerWorkImg');
        $api->post('draft/registerPhotographerWorkImg', 'DraftController@registerPhotographerWorkImgStore');
        $api->get('draft/registerPhotographerWork', 'DraftController@registerPhotographerWork');
        $api->post('draft/registerPhotographerWork', 'DraftController@registerPhotographerWorkStore');
        $api->get('draft/registerPhotographer', 'DraftController@registerPhotographer');
        $api->post('draft/registerPhotographer', 'DraftController@registerPhotographerStore');
        //摄影师
        $api->get('photographer/info', 'PhotographerController@info');
        $api->get('photographer/works', 'PhotographerController@works');
        $api->get('photographer/work', 'PhotographerController@work');
    });
});

#默认指定的不是v2版本，需要先设置请求头 #Accept:application/[配置项standardsTrss].[配置项subtype].v2+json，再通过 {host}/{前缀}/{接口名} 访问
$api->version('v2',function ($api){
    $api->get('version',function(){
        return "v2";
    });
});
