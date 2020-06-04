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

Route::middleware('auth:api')->get(
    '/user',
    function (Request $request) {
        return $request->user();
    }
);


//后台
Route::group(
    ['prefix' => 'admin', 'namespace' => 'Admin'],
    function () {
        Route::any('system/file/upload', 'System\FileController@upload');
        Route::any('system/file/ueditorUploadConfig', 'System\FileController@ueditorUploadConfig');
        Route::any('system/file/ueditorList', 'System\FileController@ueditorList');
        Route::any('system/file/ueditorCatchImage', 'System\FileController@ueditorCatchImage');
    }
);

//前台
$api = app(\Dingo\Api\Routing\Router::class);
#默认指定的是v1版本和前缀方式，则直接通过 {host}/{前缀}/{接口名} 访问即可
$api->version(
    'v1',
    ['namespace' => '\App\Http\Controllers\Api'],
    function ($api) {
        #api.throttle中间件是限制请求次数 每expires分钟只能请求limit次
        $api->group(
            ['middleware' => 'api.throttle', 'limit' => 1000, 'expires' => 1],
            function ($api) {
                //系统通用
                $api->post('sendSmsCode', 'SystemController@sendSmsCode');
                $api->get('getHelpNotes', 'SystemController@getHelpNotes');
                $api->get('getHelpTags', 'SystemController@getHelpTags');
                $api->get('getRegion', 'SystemController@getRegion');
                $api->get('getProvinces', 'SystemController@getProvinces');
                $api->get('getCitys', 'SystemController@getCitys');
                $api->get('getAreas', 'SystemController@getAreas');
                $api->get('photographerRanks', 'SystemController@photographerRanks');
                $api->get('photographerWorkCategories', 'SystemController@photographerWorkCategories');
                $api->get('PhotographerWorkCustomerIndustries', 'SystemController@PhotographerWorkCustomerIndustries');
                $api->get('visitorTags', 'SystemController@visitorTags');
                $api->get('configs', 'SystemController@configs');
                $api->get('baiduDlink', 'SystemController@baiduDlink');
                //微信用户登录
                $api->get('login', 'LoginController@mpLogin');
                $api->post('login', 'LoginController@login');
                //微信用户中心
                $api->get('my/logout', 'MyController@logout');
                $api->get('my/refresh', 'MyController@refresh');
                $api->post('my/saveInfo', 'MyController@saveInfo');
                $api->post('my/saveMobile', 'MyController@saveMobile');
                $api->get('my/info', 'MyController@info');
                $api->post('my/photographerInfo', 'MyController@savePhotographerInfo');
                $api->post('my/photographerAvatar', 'MyController@savePhotographerAvatar');
                $api->post('my/photographerBgImg', 'MyController@savePhotographerBgImg');
                $api->get('my/photographerInfo', 'MyController@photographerInfo');
                $api->get('my/photographerWorks', 'MyController@photographerWorks');
                $api->get('my/photographerWork', 'MyController@photographerWork');
                $api->get('my/photographerWorkSources', 'MyController@photographerWorkSources');
                $api->get('my/photographerWorkSourcesSimple', 'MyController@photographerWorkSourcesSimple');
                $api->get('my/setRoof', 'MyController@setRoof');
                $api->delete('my/photographerWork', 'MyController@photographerWorkDelete');
                $api->get('my/identity', 'MyController@identity');
                $api->get('my/addPhotographerWork', 'DraftController@addPhotographerWork');
                $api->Post('my/addPhotographerWork', 'DraftController@addPhotographerWorkStore');
                $api->get('my/addPhotographerWorkSource', 'DraftController@addPhotographerWorkSource');
                $api->post('my/addPhotographerWorkSource', 'DraftController@addPhotographerWorkSourceStore');
                $api->post('my/savePhotographerWorkInfo', 'MyController@savePhotographerWorkInfo');
                $api->get('my/viewRecords', 'MyController@viewRecords');
                $api->get('my/viewRecords', 'MyController@viewRecords');
                $api->get('my/photographerStatistics', 'MyController@photographerStatistics');
                $api->get('my/photographerShare' , 'MyController@photographerShare');
                $api->get('my/photographerWorkShare' , 'MyController@photographerWorkShare');
                // 用户作品 成片个数 拍摄时长 项目金额
                $api->get('my/photographerWorkHide' , 'MyController@photographerWorkHide');

                //用户注册
                $api->get('draft/registerPhotographerWorkSource', 'DraftController@registerPhotographerWorkSource');
                $api->post(
                    'draft/registerPhotographerWorkSource',
                    'DraftController@registerPhotographerWorkSourceStore'
                );
                $api->get('draft/registerPhotographerWork', 'DraftController@registerPhotographerWork');
                $api->post('draft/registerPhotographerWork', 'DraftController@registerPhotographerWorkStore');
                $api->get('draft/registerPhotographer', 'DraftController@registerPhotographer');
                $api->post('draft/registerPhotographer', 'DraftController@registerPhotographerStore');
                //用户
                $api->get('photographer/info', 'PhotographerController@info');
                $api->get('photographer/works', 'PhotographerController@works');
                $api->get('photographer/work', 'PhotographerController@work');
                $api->get('photographer/workNext', 'PhotographerController@workNext');
                $api->get('photographer/xacodeNext', 'PhotographerController@xacodeNext');
                $api->get('photographer/workSource', 'PhotographerController@workSource');
                $api->get('photographer/poster', 'PhotographerController@poster');
                $api->get('photographer/poster2', 'PhotographerController@poster2');
                $api->get('photographer/workPoster', 'PhotographerController@workPoster');
                $api->get('photographer/workPoster2', 'PhotographerController@workPoster2');
                $api->get('photographer/workPoster3', 'PhotographerController@workPoster3');
                $api->get('photographer/randomWorkPoster', 'PhotographerController@randomWorkPoster');
                $api->get('photographer/workResourcePoster', 'PhotographerController@workResourcePoster');


                $api->get('templates/list', 'PhotographerController@getTemplates');
                $api->get('photographer/rankingList', 'PhotographerController@rankingList');
                //游客
                $api->get('randomPhotographers', 'MyController@randomPhotographers');
                //访问
                $api->post('visit/inRecord', 'VisitController@inRecord');
                $api->post('visit/shareRecord', 'VisitController@shareRecord');
                $api->post('visit/operateRecord', 'VisitController@operateRecord');
                $api->get('visit/unreadCount', 'VisitController@unreadCount');
                $api->post('visit/remind', 'VisitController@setRemind');
                $api->post('visit/tag', 'VisitController@setTag');
                $api->get('visit/tags', 'VisitController@tags');
                $api->get('visit/filterItems', 'VisitController@filterItems');
                $api->get('visit/visitors', 'VisitController@visitors');
                $api->get('visit/visitor', 'VisitController@visitor');
                $api->get('visit/visitorRecords', 'VisitController@visitorRecords');
                $api->post('visit/visitorDateRecords', 'VisitController@visitorDateRecords');
                //PDF
                $api->post('pdf/save', 'MyController@saveDocPdf');
                $api->get('pdf/list', 'MyController@docPdfs');
                $api->get('pdf/getStatus', 'MyController@getDocPdfStatus');
                $api->delete('pdf/one', 'MyController@docPdfDelete');
                //百度网盘
                $api->get('baidu/isOauth', 'BaiduController@isOauth');
                $api->delete('baidu/oauth', 'BaiduController@clearOauth');
                $api->get('baidu/oauth', 'BaiduController@getOauth');
                $api->post('baidu/oauth', 'SystemController@baiduOauthStore');
                $api->get('baidu/nasUinfo', 'BaiduController@getNasUinfo');
                $api->get('baidu/fileList', 'BaiduController@getFileList');
                $api->get('baidu/fileSearch', 'BaiduController@getFileSearch');
                $api->post('baidu/qiniuFetchPan', 'BaiduController@qiniuFetchPan');
                //七牛
                $api->get('qiniu/getParams', 'QiniuController@getParams');
                //通知
                $api->post('notify/qiniu/fetch', 'Notify\QiniuController@fetch');
                $api->post('notify/qiniu/fopDeal', 'Notify\QiniuController@fopDeal');
                $api->post('notify/qiniu/fopRich', 'Notify\QiniuController@fopRich');
                // 邀请码
                $api->post('invote/used', 'InvoteCodeController@used');
                $api->post('invote/query', 'InvoteCodeController@query');
                $api->post('invote/update', 'InvoteCodeController@update');
                // 众筹相关
                $api->get('crowdfunding/getData', 'CrowdFundingController@getData');
                $api->post('crowdfunding/order', 'CrowdFundingController@order');
                $api->post('crowdfunding/log', 'CrowdFundingController@log');
                $api->post('notify/miniprogram/crowdfunding', 'Notify\MiniProgramController@crowdfunding');

                // 大咖
                $api->post('star/getStars', 'StarController@getStars');
                $api->get('star/test' , 'StarController@test');
                $api->get('star/test2' , 'StarController@test2');
                $api->get('star/test3' , 'StarController@test3');
                $api->get('star/test4' , 'StarController@test4');
                $api->post('star/upload' , 'StarController@upload');

                // 查询用户是否使用过引导
                $api->post('bootstrap/query' , 'BootstrapController@query');

                // 问题手机
                $api->post('question/collect' , 'QuestionController@collect');
                $api->get('question/getPage' , 'QuestionController@getPage');

                // 目标用户
                $api->post('target/insert' , 'TargetUserController@insert');


            }
        );
    }
);

#默认指定的不是v2版本，需要先设置请求头 #Accept:application/[配置项standardsTrss].[配置项subtype].v2+json，再通过 {host}/{前缀}/{接口名} 访问
$api->version(
    'v2',
    function ($api) {
        $api->get(
            'version',
            function () {
                return "v2";
            }
        );
    }
);
