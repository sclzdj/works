<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});
/*授权*/
Route::get('oauth/baidu/pan', 'Index\Oauth\BaiduController@pan');

/**后台**/
//重置路由跳回自己的首页
Route::get('/', 'Admin\Auth\LoginController@showLoginForm');
Route::get('image_storage', 'Admin\System\FileController@image');
//正式定义后台路由
Route::group(['prefix' => 'admin', 'namespace' => 'Admin'], function () {
    //重置路由跳回自己的首页
    Route::get('/', 'Auth\LoginController@showLoginForm');
    Route::get('/index', 'Auth\LoginController@showLoginForm');
    //这里面写需要登录的路由
    Route::group(['middleware' => ['auth:admin', 'permission']], function () {
        //系统模块
        Route::get('system/index/index', 'System\IndexController@index');//系统首页
        Route::any('system/index/config',
                   'System\IndexController@config');//系统配置
        Route::any('system/index/updatePassword',
                   'System\IndexController@updatePassword');//修改密码
        Route::any('system/index/setInfo',
                   'System\IndexController@setInfo');//资料设置
        Route::resource('system/user', 'System\UserController');//账号管理
        Route::patch('system/user/{id}/enable', 'System\UserController@enable');
        Route::patch('system/user/{id}/disable',
                     'System\UserController@disable');
        Route::resource('system/role', 'System\RoleController');//角色管理
        Route::patch('system/role/{id}/enable', 'System\RoleController@enable');
        Route::patch('system/role/{id}/disable',
                     'System\RoleController@disable');
        Route::resource('system/node', 'System\NodeController');//节点管理
        Route::patch('system/node/{id}/enable', 'System\NodeController@enable');
        Route::patch('system/node/{id}/disable',
                     'System\NodeController@disable');
        Route::post('system/node/sort', 'System\NodeController@sort');
        Route::any('system/node/module/sort',
                   'System\NodeController@moduleSort');
        Route::get('system/file', 'System\FileController@index');//文件管理
        Route::delete('system/file', 'System\FileController@destroy');
        Route::get('system/area', 'System\AreaController@index');//地区管理
        Route::post('system/area/sort', 'System\AreaController@sort');


        Route::get('system/demo/ueditor', 'System\DemoController@ueditor');//demo管理
        Route::post('system/demo/ueditor', 'System\DemoController@ueditorSave');
        Route::get('system/demo/webuploaderImage', 'System\DemoController@webuploaderImage');
        Route::post('system/demo/webuploaderImage', 'System\DemoController@webuploaderImageSave');
        Route::get('system/demo/webuploaderImages', 'System\DemoController@webuploaderImages');
        Route::post('system/demo/webuploaderImages', 'System\DemoController@webuploaderImagesSave');
        Route::get('system/demo/webuploaderFile', 'System\DemoController@webuploaderFile');
        Route::post('system/demo/webuploaderFile', 'System\DemoController@webuploaderFileSave');
        Route::get('system/demo/webuploaderFiles', 'System\DemoController@webuploaderFiles');
        Route::post('system/demo/webuploaderFiles', 'System\DemoController@webuploaderFilesSave');
        Route::get('system/demo/tags', 'System\DemoController@tags');
        Route::post('system/demo/tags', 'System\DemoController@tagsSave');
        Route::get('system/demo/select2', 'System\DemoController@select2');
        Route::post('system/demo/select2', 'System\DemoController@select2Save');

        //云作品模块
        Route::any('works/index/config', 'Works\IndexController@config');//
        Route::resource('works/helpNote', 'Works\HelpNoteController');//帮助管理
        Route::any('works/helpNoteSort', 'Works\HelpNoteController@sort');
        Route::resource('works/photographer', 'Works\PhotographerController');//摄影师管理
        Route::resource('works/photographerWork', 'Works\PhotographerWorkController');//作品集管理
    });
    //这下面写不需要登录的路由
    Route::get('login', 'Auth\LoginController@showLoginForm');//账号登录
    Route::post('login', 'Auth\LoginController@login');
    Route::post('logout', 'Auth\LoginController@logout');//账号退出
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
