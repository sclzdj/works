<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\PhotographerRequest;
use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerInfoTag;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\User;
use App\Model\Index\Visitor;
use App\Servers\AliSendShortMessageServer;
use App\Servers\ArrServer;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use App\Servers\WechatServer;
use Illuminate\Http\Request;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

/**
 * 草稿相关
 * Class DraftController
 * @package App\Http\Controllers\Api
 */
class DraftController extends UserGuardController
{
    /**
     * 查出用户注册项目资源
     * @return mixed|void
     */
    public function registerPhotographerWorkSource()
    {
        $this->notVisitorIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = $this->_photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(['status' => 0])->first();
            if (!$photographer_work) {
                $photographer_work = PhotographerWork::create();
                $photographer_work->photographer_id = $photographer->id;
                $photographer_work->save();
            }
            $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
                PhotographerWorkSource::allowFields()
            )->where('status', 200)->orderBy('sort', 'asc')->get();
            $photographer_work_sources = SystemServer::getPhotographerWorkSourcesThumb($photographer_work_sources);
            $photographer_work_sources = $photographer_work_sources->toArray();
            \DB::commit();//提交事务

            return $this->responseParseArray($photographer_work_sources);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存用户注册项目资源
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerWorkSourceStore(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
        $asynchronous_task = [];
        \DB::beginTransaction();//开启事务
        try {
            $photographer = $this->_photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(
                ['status' => 0]
            )->first();
            if (!$photographer_work) {
                $photographer_work = PhotographerWork::create();
                $photographer_work->photographer_id = $photographer->id;
                $photographer_work->save();
            }
            $photographer_work->photographerWorkSources()->where(['status' => 200])->update(['status' => 300]);
            foreach ($request->sources as $k => $v) {
                $photographer_work_source = PhotographerWorkSource::where(
                    ['photographer_work_id' => $photographer_work->id, 'status' => 300, 'key' => $v['key']]
                )->first();
                if ($photographer_work_source) {
                    $photographer_work_source->sort = $k + 1;
                    $photographer_work_source->status = 200;
                    $photographer_work_source->save();
                } else {
                    $photographer_work_source = PhotographerWorkSource::create();
                    $photographer_work_source->photographer_work_id = $photographer_work->id;
                    $photographer_work_source->key = $v['key'];
                    $photographer_work_source->url = $v['url'];
                    if ($v['type'] != 'image') {
                        $photographer_work_source->deal_key = $v['key'];
                        $photographer_work_source->deal_url = $v['url'];
                        $photographer_work_source->rich_key = $v['key'];
                        $photographer_work_source->rich_url = $v['url'];
                    }
                    $photographer_work_source->type = $v['type'];
                    $photographer_work_source->origin = $v['origin'];
                    $photographer_work_source->sort = $k + 1;
                    $photographer_work_source->status = 200;
                    $photographer_work_source->save();
                    if ($photographer_work_source->type == 'image') {
                        $photographer_work_source->is_new_source = 1;
                        $photographer_work_source->save();
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?imageInfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                                $photographer_work_source->size = $res['data']['size'];
                                $photographer_work_source->width = $res['data']['width'];
                                $photographer_work_source->height = $res['data']['height'];
//                                $photographer_work_source->deal_size = $res['data']['size'];
//                                $photographer_work_source->deal_width = $res['data']['width'];
//                                $photographer_work_source->deal_height = $res['data']['height'];
//                                $photographer_work_source->rich_size = $res['data']['size'];
//                                $photographer_work_source->rich_width = $res['data']['width'];
//                                $photographer_work_source->rich_height = $res['data']['height'];
                                $photographer_work_source->save();
                                /*平均色调*/
                                $res_ave = SystemServer::request('GET', $photographer_work_source->url.'?imageAve');
                                if ($res_ave['code'] == 200) {
                                    if (!isset($res_ave['data']['error']) || (isset($res_ave['data']['code']) && $res_ave['data']['code'] == 200)) {
                                        if (isset($res_ave['data']['RGB'])) {
                                            $photographer_work_source->image_ave = $res_ave['data']['RGB'];
                                            $photographer_work_source->save();
                                        }
                                    }
                                }
                                /*平均色调 END*/
                                /*exif*/
                                PhotographerWorkSource::where('id', $photographer_work_source->id)->update(
                                    [
                                        'exif' => json_encode([]),
                                    ]
                                );
                                $res_exif = SystemServer::request('GET', $photographer_work_source->url.'?exif');
                                if ($res_exif['code'] == 200) {
                                    if (!isset($res_exif['data']['error']) || (isset($res_exif['data']['code']) && $res_exif['data']['code'] == 200)) {
                                        PhotographerWorkSource::where('id', $photographer_work_source->id)->update(
                                            [
                                                'exif' => json_encode($res_exif['data']),
                                            ]
                                        );
                                    }
                                }
                                /*exif END*/
                                $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
                                $bucket = 'zuopin';
                                $asynchronous_task[] = [
                                    'task_type' => 'qiniuPfop',
                                    'bucket' => $bucket,
                                    'key' => $photographer_work_source->key,
                                    'fops' => $fops,
                                    'pipeline' => null,
                                    'notifyUrl' => config(
                                            'app.url'
                                        ).'/api/notify/qiniu/fopDeal?photographer_work_source_id='.$photographer_work_source->id,
                                    'useHTTPS' => true,
                                    'error_step' => '处理图片持久请求',
                                    'error_msg' => '七牛持久化接口返回错误信息',
                                    'error_request_data' => $request->all(),
                                    'error_photographerWorkSource' => $photographer_work_source,
                                ];
                            } else {
                                $asynchronous_task[] = [
                                    'task_type' => 'error_qiniuNotifyFop',
                                    'step' => '原始图片信息请求',
                                    'msg' => '七牛图片信息接口返回错误信息',
                                    'request_data' => $request->all(),
                                    'photographerWorkSource' => $photographer_work_source,
                                    'res' => $res['data'],
                                ];
                            }
                        } else {
                            $asynchronous_task[] = [
                                'task_type' => 'error_qiniuNotifyFop',
                                'step' => '原始图片信息请求',
                                'msg' => '请求七牛图片信息接口报错：'.$res['msg'],
                                'request_data' => $request->all(),
                                'photographerWorkSource' => $photographer_work_source,
                                'res' => $res,
                            ];
                        }
                    } elseif ($photographer_work_source->type == 'video') {
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?avinfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                                $photographer_work_source->size = $res['data']['format']['size'];
                                $photographer_work_source->deal_size = $res['data']['format']['size'];
                                $photographer_work_source->rich_size = $res['data']['format']['size'];
                                $photographer_work_source->save();
                            } else {
                                $asynchronous_task[] = [
                                    'task_type' => 'error_qiniuNotifyFop',
                                    'step' => '原始视频信息请求',
                                    'msg' => '七牛视频信息接口返回错误信息',
                                    'request_data' => $request->all(),
                                    'photographerWorkSource' => $photographer_work_source,
                                    'res' => $res['data'],
                                ];
                            }
                        } else {
                            $asynchronous_task[] = [
                                'task_type' => 'error_qiniuNotifyFop',
                                'step' => '原始视频信息请求',
                                'msg' => '请求七牛视频信息接口报错：'.$res['msg'],
                                'request_data' => $request->all(),
                                'photographerWorkSource' => $photographer_work_source,
                                'res' => $res,
                            ];
                        }
                    }
                }
            }
            $photographer_work->photographerWorkSources()->where(['status' => 300])->update(['status' => 400]);
            \DB::commit();//提交事务
            foreach ($asynchronous_task as $task) {
                if ($task['task_type'] == 'qiniuPfop') {
                    $qrst = SystemServer::qiniuPfop(
                        $task['bucket'],
                        $task['key'],
                        $task['fops'],
                        $task['pipeline'],
                        $task['notifyUrl'],
                        $task['useHTTPS']
                    );
                    if ($qrst['err']) {
                        ErrLogServer::qiniuNotifyFop(
                            $task['error_step'],
                            $task['error_msg'],
                            $task['error_request_data'],
                            $task['error_photographerWorkSource'],
                            $qrst['err']
                        );
                    }
                } elseif ($task['task_type'] == 'editRunGenerateWatermark') {
                    PhotographerWorkSource::editRunGenerateWatermark(
                        $task['photographer_work_source_id'],
                        $task['edit_node']
                    );
                } elseif ($task['task_type'] == 'error_qiniuNotifyFop') {
                    ErrLogServer::qiniuNotifyFop(
                        $task['step'],
                        $task['msg'],
                        $task['request_data'],
                        $task['photographerWorkSource'],
                        $task['res']
                    );
                }
            }

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 查出用户注册项目信息
     * @return mixed|void
     */
    public function registerPhotographerWork()
    {
        $this->notVisitorIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = $this->_photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(['status' => 0])->first();
            if (!$photographer_work) {
                $photographer_work = PhotographerWork::create();
                $photographer_work->photographer_id = $photographer->id;
                $photographer_work->save();
            }
            $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                PhotographerWorkTag::allowFields()
            )->get()->toArray();
            $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
            $photographer_work = ArrServer::toNullStrData(
                $photographer_work,
                ['sheets_number', 'shooting_duration']
            );
            $photographer_work['tags'] = $photographer_work_tags;
            $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
            $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
            \DB::commit();//提交事务

            return $this->responseParseArray($photographer_work);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存用户注册项目信息
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerWorkStore(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = $this->_photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(
                ['status' => 0]
            )->first();
            if (!$photographer_work) {
                $photographer_work = PhotographerWork::create();
                $photographer_work->photographer_id = $photographer->id;
                $photographer_work->save();
            }
            $scene = '1/'.$photographer_work->id;
            if (!$photographer_work->xacode) {
                $xacode_res = WechatServer::generateXacode($scene, false);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer_work->xacode = $xacode_res['xacode'];
            }
            if (!$photographer_work->xacode_hyaline) {
                $xacode_res = WechatServer::generateXacode($scene);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer_work->xacode_hyaline = $xacode_res['xacode'];
            }
            $photographer_work->name = $request->name;
            $photographer_work->describe = $request->describe;
            $photographer_work->is_business = $request->is_business;
            $photographer_work->location = $request->location;
            $photographer_work->address = $request->address;
            $photographer_work->latitude = $request->latitude;
            $photographer_work->longitude = $request->longitude;
            $photographer_work->customer_name = $request->customer_name;
            $photographer_work->photographer_work_customer_industry_id = $request->photographer_work_customer_industry_id;
            $photographer_work->project_amount = $request->project_amount;
            $photographer_work->hide_project_amount = $request->hide_project_amount;
            $photographer_work->sheets_number = $request->sheets_number;
            $photographer_work->hide_sheets_number = $request->hide_sheets_number;
            $photographer_work->shooting_duration = $request->shooting_duration;
            $photographer_work->hide_shooting_duration = $request->hide_shooting_duration;
            $photographer_work->photographer_work_category_id = $request->photographer_work_category_id;
            $photographer_work->save();
            PhotographerWorkTag::where(['photographer_work_id' => $photographer_work->id])->delete();
            if ($request->tags) {
                foreach ($request->tags as $v) {
                    $photographer_work_tag = PhotographerWorkTag::create();
                    $photographer_work_tag->photographer_work_id = $photographer_work->id;
                    $photographer_work_tag->name = $v;
                    $photographer_work_tag->save();
                }
            }
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存用户注册项目信息(直接完成注册)
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerWorkStore2(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
        $user = auth($this->guard)->user();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = $this->_photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(
                ['status' => 0]
            )->first();
            if (!$photographer_work) {
                $photographer_work = PhotographerWork::create();
                $photographer_work->photographer_id = $photographer->id;
                $photographer_work->save();
            }
            $scene = '1/'.$photographer_work->id;
            if (!$photographer_work->xacode) {
                $xacode_res = WechatServer::generateXacode($scene, false);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer_work->xacode = $xacode_res['xacode'];
            }
            if (!$photographer_work->xacode_hyaline) {
                $xacode_res = WechatServer::generateXacode($scene);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer_work->xacode_hyaline = $xacode_res['xacode'];
            }
            $photographer_work->name = $request->name;
            $photographer_work->describe = $request->describe;
            $photographer_work->is_business = $request->is_business;
            $photographer_work->location = $request->location;
            $photographer_work->address = $request->address;
            $photographer_work->latitude = $request->latitude;
            $photographer_work->longitude = $request->longitude;
            $photographer_work->customer_name = $request->customer_name;
            $photographer_work->photographer_work_customer_industry_id = $request->photographer_work_customer_industry_id;
            $photographer_work->project_amount = $request->project_amount;
            $photographer_work->hide_project_amount = $request->hide_project_amount;
            $photographer_work->sheets_number = $request->sheets_number;
            $photographer_work->hide_sheets_number = $request->hide_sheets_number;
            $photographer_work->shooting_duration = $request->shooting_duration;
            $photographer_work->hide_shooting_duration = $request->hide_shooting_duration;
            $photographer_work->photographer_work_category_id = $request->photographer_work_category_id;
            $photographer_work->save();
            PhotographerWorkTag::where(['photographer_work_id' => $photographer_work->id])->delete();
            if ($request->tags) {
                foreach ($request->tags as $v) {
                    $photographer_work_tag = PhotographerWorkTag::create();
                    $photographer_work_tag->photographer_work_id = $photographer_work->id;
                    $photographer_work_tag->name = $v;
                    $photographer_work_tag->save();
                }
            }

            $photographer->avatar = $user->avatar;
            $photographer->name = $user->nickname;
            $photographer->gender = $user->gender;
            $photographer->mobile = $user->purePhoneNumber;
            $photographer->created_at = date('Y-m-d H:i:s');
            $photographer->status = 200;
            $scene = '0/'.$photographer->id;
            if (!$photographer->xacode) {
                $xacode_res = WechatServer::generateXacode($scene, false);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer->xacode = $xacode_res['xacode'];
            }
            if (!$photographer->xacode_hyaline) {
                $xacode_res = WechatServer::generateXacode($scene);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer->xacode_hyaline = $xacode_res['xacode'];
            }
            $photographer_work->status = 200;
            $photographer_work->save();
            $photographerWorkSources = $photographer_work->photographerWorkSources()->where(
                ['status' => 200, 'type' => 'image']
            )->orderBy('sort', 'asc')->get();
            if ($photographerWorkSources) {
                foreach ($photographerWorkSources as $photographerWorkSource) {
                    $photographerWorkSource->is_new_source = 0;
                    $photographerWorkSource->save();
                    $asynchronous_task[] = [
                        'task_type' => 'editRunGenerateWatermark',
                        'photographer_work_source_id' => $photographerWorkSource->id,
                        'edit_node' => '用户注册',
                    ];
                }
            }
            $user->identity = 1;
            $user->save();
            $photographer->save();
            //把他作为别人的访客标为同行
            Visitor::where(['user_id' => $user->id, 'visitor_tag_id' => 0])->update(['visitor_tag_id' => 4]);
            if ($user->gh_openid != '') {
                $app = app('wechat.official_account');
                $template_id = 'rjph5uR7iIzT2rEn3LjnF65zEdKZYisUGoAVgpipxpk';
                $tmr = $app->template_message->send(
                    [
                        'touser' => $user->gh_openid,
                        'template_id' => $template_id,
                        'url' => config('app.url'),
                        'miniprogram' => [
                            'appid' => config('custom.wechat.mp.appid'),
                            'pagepath' => 'pages/homePage/homePage',//注册成功分享页
                        ],
                        'data' => [
                            'first' => '你的云作品已创建成功。',
                            'keyword1' => $photographer->name,
                            'keyword2' => SystemArea::where('id', $photographer->city)->value('short_name'),
                            'keyword3' => PhotographerRank::where('id', $photographer->photographer_rank_id)->value(
                                    'name'
                                ).'摄影师',
                            'keyword4' => $photographer->wechat,
                            'keyword5' => $photographer->mobile,
                            'remark' => '云作品客服微信'.SystemConfig::getVal('customer_wechat', 'works'),
                        ],
                    ]
                );
                if ($tmr['errcode'] != 0) {
                    ErrLogServer::SendWxGhTemplateMessage($template_id, $user->gh_openid, $tmr['errmsg'], $tmr);
                }
            }
            if ($photographer->mobile) {//发送短信
                $third_type = config('custom.send_short_message.third_type');
                $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
                if ($third_type == 'ali') {
                    AliSendShortMessageServer::quickSendSms(
                        $photographer->mobile,
                        $TemplateCodes,
                        'register_success',
                        ['name' => $photographer->name]
                    );
                }
            }
            \DB::commit();//提交事务
            foreach ($asynchronous_task as $task) {
                if ($task['task_type'] == 'editRunGenerateWatermark') {
                    PhotographerWorkSource::editRunGenerateWatermark(
                        $task['photographer_work_source_id'],
                        $task['edit_node']
                    );
                } elseif ($task['task_type'] == 'error_qiniuNotifyFop') {
                    ErrLogServer::qiniuNotifyFop(
                        $task['step'],
                        $task['msg'],
                        $task['request_data'],
                        $task['photographerWorkSource'],
                        $task['res']
                    );
                }
            }

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 查出用户注册信息
     * @return mixed|void
     */
    public function registerPhotographer()
    {
        $this->notVisitorIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
        $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer = SystemServer::parseRegionName($photographer);
        $photographer = SystemServer::parsePhotographerRank($photographer);
        $photographer_info_tags = PhotographerInfoTag::where(
            [
                'photographer_id' => $photographer['id'],
            ]
        )->get();
        $photographer['auth_tags'] = [];
        $photographer['award_tags'] = [];
        $photographer['educate_tags'] = [];
        $photographer['equipment_tags'] = [];
        $photographer['social_tags'] = [];
        foreach ($photographer_info_tags as $photographer_info_tag) {
            switch ($photographer_info_tag->type) {
                case 'auth':
                    $photographer['auth_tags'][] = $photographer_info_tag->name;
                    break;
                case 'award':
                    $photographer['award_tags'][] = $photographer_info_tag->name;
                    break;
                case 'educate':
                    $photographer['educate_tags'][] = $photographer_info_tag->name;
                    break;
                case 'equipment':
                    $photographer['equipment_tags'][] = $photographer_info_tag->name;
                    break;
                case 'social':
                    $photographer['social_tags'][] = $photographer_info_tag->name;
                    break;
            }
        }

        return $this->responseParseArray($photographer);

    }

    /**
     * 保存用户注册信息
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerStore(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
        $asynchronous_task = [];
        \DB::beginTransaction();//开启事务
        try {
            //验证短信验证码
            $verify_result = SystemServer::verifySmsCode(
                $request->mobile,
                $request->sms_code,
                'photographer_register',
                $request->getClientIp()
            );
            if ($verify_result['status'] != 'SUCCESS') {
                \DB::rollback();//回滚事务

                return $this->response->error($verify_result['message'], 500);
            }
            $user = auth($this->guard)->user();
            $photographer = $this->_photographer(null, $this->guard);
            //验证手机号的唯一性
            $other_photographer = Photographer::where('id', '!=', $photographer->id)->where(
                ['mobile' => $request->mobile, 'status' => 200]
            )->first();
            if ($other_photographer) {
                \DB::rollback();//回滚事务

                return $this->response->error('该手机号已经创建过云作品', 500);
            }
            if ($request->avatar) {
                $photographer->avatar = (string)$request->avatar;
            } else {
                $photographer->avatar = $user->avatar;
            }
            $photographer->name = $request->name;
            $photographer->gender = $request->gender ?? 0;
            $photographer->province = $request->province;
            $photographer->city = $request->city;
            $photographer->area = $request->area;
            $photographer->photographer_rank_id = $request->photographer_rank_id;
            $photographer->wechat = $request->wechat;
            $photographer->mobile = $request->mobile;
            $photographer->created_at = date('Y-m-d H:i:s');
            $photographer->status = 200;
            $scene = '0/'.$photographer->id;
            if (!$photographer->xacode) {
                $xacode_res = WechatServer::generateXacode($scene, false);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer->xacode = $xacode_res['xacode'];
            }
            if (!$photographer->xacode_hyaline) {
                $xacode_res = WechatServer::generateXacode($scene);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer->xacode_hyaline = $xacode_res['xacode'];
            }
            $photographer->save();
            if ($request->auth_tags) {
                foreach ($request->auth_tags as $auth_tag) {
                    PhotographerInfoTag::create(
                        [
                            'photographer_id' => $photographer->id,
                            'type' => 'auth',
                            'name' => $auth_tag,
                        ]
                    );
                }
            }
            if ($request->award_tags) {
                foreach ($request->award_tags as $award_tag) {
                    PhotographerInfoTag::create(
                        [
                            'photographer_id' => $photographer->id,
                            'type' => 'award',
                            'name' => $award_tag,
                        ]
                    );
                }
            }
            if ($request->educate_tags) {
                foreach ($request->educate_tags as $educate_tag) {
                    PhotographerInfoTag::create(
                        [
                            'photographer_id' => $photographer->id,
                            'type' => 'educate',
                            'name' => $educate_tag,
                        ]
                    );
                }
            }
            if ($request->equipment_tags) {
                foreach ($request->equipment_tags as $equipment_tag) {
                    PhotographerInfoTag::create(
                        [
                            'photographer_id' => $photographer->id,
                            'type' => 'equipment',
                            'name' => $equipment_tag,
                        ]
                    );
                }
            }
            if ($request->social_tags) {
                foreach ($request->social_tags as $social_tag) {
                    PhotographerInfoTag::create(
                        [
                            'photographer_id' => $photographer->id,
                            'type' => 'social',
                            'name' => $social_tag,
                        ]
                    );
                }
            }
            $photographer_work = $photographer->photographerWorks()->where(['status' => 0])->first();
            if (!$photographer_work) {
                \DB::rollback();//回滚事务

                return $this->response->error('项目不存在', 500);
            }
            $photographer_work->status = 200;
            $photographer_work->save();
            $photographerWorkSources = $photographer_work->photographerWorkSources()->where(
                ['status' => 200, 'type' => 'image']
            )->orderBy('sort', 'asc')->get();
            if ($photographerWorkSources) {
                foreach ($photographerWorkSources as $photographerWorkSource) {
                    $photographerWorkSource->is_new_source = 0;
                    $photographerWorkSource->save();
                    $asynchronous_task[] = [
                        'task_type' => 'editRunGenerateWatermark',
                        'photographer_work_source_id' => $photographerWorkSource->id,
                        'edit_node' => '用户注册',
                    ];
                }
            }
            $user->identity = 1;
            $user->save();
            //把他作为别人的访客标为同行
            Visitor::where(['user_id' => $user->id, 'visitor_tag_id' => 0])->update(['visitor_tag_id' => 4]);
            if ($user->gh_openid != '') {
                $app = app('wechat.official_account');
                $template_id = 'rjph5uR7iIzT2rEn3LjnF65zEdKZYisUGoAVgpipxpk';
                $tmr = $app->template_message->send(
                    [
                        'touser' => $user->gh_openid,
                        'template_id' => $template_id,
                        'url' => config('app.url'),
                        'miniprogram' => [
                            'appid' => config('custom.wechat.mp.appid'),
                            'pagepath' => 'pages/homePage/homePage',//注册成功分享页
                        ],
                        'data' => [
                            'first' => '你的云作品已创建成功。',
                            'keyword1' => $photographer->name,
                            'keyword2' => SystemArea::where('id', $photographer->city)->value('short_name'),
                            'keyword3' => PhotographerRank::where('id', $photographer->photographer_rank_id)->value(
                                    'name'
                                ).'摄影师',
                            'keyword4' => $photographer->wechat,
                            'keyword5' => $photographer->mobile,
                            'remark' => '云作品客服微信'.SystemConfig::getVal('customer_wechat', 'works'),
                        ],
                    ]
                );
                if ($tmr['errcode'] != 0) {
                    ErrLogServer::SendWxGhTemplateMessage($template_id, $user->gh_openid, $tmr['errmsg'], $tmr);
                }
            }
            if ($photographer->mobile) {//发送短信
                $third_type = config('custom.send_short_message.third_type');
                $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
                if ($third_type == 'ali') {
                    AliSendShortMessageServer::quickSendSms(
                        $photographer->mobile,
                        $TemplateCodes,
                        'register_success',
                        ['name' => $photographer->name]
                    );
                }
            }
            \DB::commit();//提交事务
            foreach ($asynchronous_task as $task) {
                if ($task['task_type'] == 'editRunGenerateWatermark') {
                    PhotographerWorkSource::editRunGenerateWatermark(
                        $task['photographer_work_source_id'],
                        $task['edit_node']
                    );
                } elseif ($task['task_type'] == 'error_qiniuNotifyFop') {
                    ErrLogServer::qiniuNotifyFop(
                        $task['step'],
                        $task['msg'],
                        $task['request_data'],
                        $task['photographerWorkSource'],
                        $task['res']
                    );
                }
            }

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 查出添加用户项目资源
     * @return mixed|void
     */
    public function addPhotographerWorkSource()
    {
        $this->notPhotographerIdentityVerify();
        $photographer_work = $this->_photographer(null, $this->guard)->photographerWorks()->where(
            ['status' => 0]
        )->first();
        if ($photographer_work) {
            $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
                PhotographerWorkSource::allowFields()
            )->where('status', 200)->orderBy('sort', 'asc')->get();
            $photographer_work_sources = SystemServer::getPhotographerWorkSourcesThumb($photographer_work_sources);
            $photographer_work_sources = $photographer_work_sources->toArray();
        } else {
            $photographer_work_sources = [];
        }

        return $this->responseParseArray($photographer_work_sources);
    }

    /**
     * 保存添加用户项目资源
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function addPhotographerWorkSourceStore(PhotographerRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $asynchronous_task = [];
        \DB::beginTransaction();//开启事务
        try {
            $photographer = $this->_photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(
                ['status' => 0]
            )->first();
            if (!$photographer_work) {
                $photographer_work = PhotographerWork::create();
                $photographer_work->photographer_id = $photographer->id;
            }
            $photographer_work->save();
            $photographer_work->photographerWorkSources()->where(['status' => 200])->update(['status' => 300]);
            foreach ($request->sources as $k => $v) {
                $photographer_work_source = PhotographerWorkSource::where(
                    ['photographer_work_id' => $photographer_work->id, 'status' => 300, 'key' => $v['key']]
                )->first();
                if ($photographer_work_source) {
                    $photographer_work_source->sort = $k + 1;
                    $photographer_work_source->status = 200;
                    $photographer_work_source->save();
                } else {
                    $photographer_work_source = PhotographerWorkSource::create();
                    $photographer_work_source->photographer_work_id = $photographer_work->id;
                    $photographer_work_source->key = $v['key'];
                    $photographer_work_source->url = $v['url'];
                    if ($v['type'] != 'image') {
                        $photographer_work_source->deal_key = $v['key'];
                        $photographer_work_source->deal_url = $v['url'];
                        $photographer_work_source->rich_key = $v['key'];
                        $photographer_work_source->rich_url = $v['url'];
                    }
                    $photographer_work_source->type = $v['type'];
                    $photographer_work_source->origin = $v['origin'];
                    $photographer_work_source->sort = $k + 1;
                    $photographer_work_source->status = 200;
                    $photographer_work_source->save();
                    if ($photographer_work_source->type == 'image') {
                        $photographer_work_source->is_new_source = 1;
                        $photographer_work_source->save();
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?imageInfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                                $photographer_work_source->size = $res['data']['size'];
                                $photographer_work_source->width = $res['data']['width'];
                                $photographer_work_source->height = $res['data']['height'];
//                                $photographer_work_source->deal_size = $res['data']['size'];
//                                $photographer_work_source->deal_width = $res['data']['width'];
//                                $photographer_work_source->deal_height = $res['data']['height'];
//                                $photographer_work_source->rich_size = $res['data']['size'];
//                                $photographer_work_source->rich_width = $res['data']['width'];
//                                $photographer_work_source->rich_height = $res['data']['height'];
                                $photographer_work_source->save();
                                /*平均色调*/
                                $res_ave = SystemServer::request('GET', $photographer_work_source->url.'?imageAve');
                                if ($res_ave['code'] == 200) {
                                    if (!isset($res_ave['data']['error']) || (isset($res_ave['data']['code']) && $res_ave['data']['code'] == 200)) {
                                        if (isset($res_ave['data']['RGB'])) {
                                            $photographer_work_source->image_ave = $res_ave['data']['RGB'];
                                            $photographer_work_source->save();
                                        }
                                    }
                                }
                                /*平均色调 END*/
                                /*exif*/
                                PhotographerWorkSource::where('id', $photographer_work_source->id)->update(
                                    [
                                        'exif' => json_encode([]),
                                    ]
                                );
                                $res_exif = SystemServer::request('GET', $photographer_work_source->url.'?exif');
                                if ($res_exif['code'] == 200) {
                                    if (!isset($res_exif['data']['error']) || (isset($res_exif['data']['code']) && $res_exif['data']['code'] == 200)) {
                                        PhotographerWorkSource::where('id', $photographer_work_source->id)->update(
                                            [
                                                'exif' => json_encode($res_exif['data']),
                                            ]
                                        );
                                    }
                                }
                                /*exif END*/
                                $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
                                $bucket = 'zuopin';
                                $asynchronous_task[] = [
                                    'task_type' => 'qiniuPfop',
                                    'bucket' => $bucket,
                                    'key' => $photographer_work_source->key,
                                    'fops' => $fops,
                                    'pipeline' => null,
                                    'notifyUrl' => config(
                                            'app.url'
                                        ).'/api/notify/qiniu/fopDeal?photographer_work_source_id='.$photographer_work_source->id,
                                    'useHTTPS' => true,
                                    'error_step' => '处理图片持久请求',
                                    'error_msg' => '七牛持久化接口返回错误信息',
                                    'error_request_data' => $request->all(),
                                    'error_photographerWorkSource' => $photographer_work_source,
                                ];
                            } else {
                                $asynchronous_task[] = [
                                    'task_type' => 'error_qiniuNotifyFop',
                                    'step' => '原始图片信息请求',
                                    'msg' => '七牛图片信息接口返回错误信息',
                                    'request_data' => $request->all(),
                                    'photographerWorkSource' => $photographer_work_source,
                                    'res' => $res['data'],
                                ];
                            }
                        } else {
                            $asynchronous_task[] = [
                                'task_type' => 'error_qiniuNotifyFop',
                                'step' => '原始图片信息请求',
                                'msg' => '请求七牛图片信息接口报错：'.$res['msg'],
                                'request_data' => $request->all(),
                                'photographerWorkSource' => $photographer_work_source,
                                'res' => $res,
                            ];
                        }
                    } elseif ($photographer_work_source->type == 'video') {
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?avinfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                                $photographer_work_source->size = $res['data']['format']['size'];
                                $photographer_work_source->deal_size = $res['data']['format']['size'];
                                $photographer_work_source->rich_size = $res['data']['format']['size'];
                                $photographer_work_source->save();
                            } else {
                                $asynchronous_task[] = [
                                    'task_type' => 'error_qiniuNotifyFop',
                                    'step' => '原始视频信息请求',
                                    'msg' => '七牛视频信息接口返回错误信息',
                                    'request_data' => $request->all(),
                                    'photographerWorkSource' => $photographer_work_source,
                                    'res' => $res['data'],
                                ];
                            }
                        } else {
                            $asynchronous_task[] = [
                                'task_type' => 'error_qiniuNotifyFop',
                                'step' => '原始视频信息请求',
                                'msg' => '请求七牛视频信息接口报错：'.$res['msg'],
                                'request_data' => $request->all(),
                                'photographerWorkSource' => $photographer_work_source,
                                'res' => $res,
                            ];
                        }
                    }
                }
            }
            $photographer_work->photographerWorkSources()->where(['status' => 300])->update(['status' => 400]);
            \DB::commit();//提交事务
            foreach ($asynchronous_task as $task) {
                if ($task['task_type'] == 'qiniuPfop') {
                    $qrst = SystemServer::qiniuPfop(
                        $task['bucket'],
                        $task['key'],
                        $task['fops'],
                        $task['pipeline'],
                        $task['notifyUrl'],
                        $task['useHTTPS']
                    );
                    if ($qrst['err']) {
                        ErrLogServer::qiniuNotifyFop(
                            $task['error_step'],
                            $task['error_msg'],
                            $task['error_request_data'],
                            $task['error_photographerWorkSource'],
                            $qrst['err']
                        );
                    }
                } elseif ($task['task_type'] == 'editRunGenerateWatermark') {
                    PhotographerWorkSource::editRunGenerateWatermark(
                        $task['photographer_work_source_id'],
                        $task['edit_node']
                    );
                } elseif ($task['task_type'] == 'error_qiniuNotifyFop') {
                    ErrLogServer::qiniuNotifyFop(
                        $task['step'],
                        $task['msg'],
                        $task['request_data'],
                        $task['photographerWorkSource'],
                        $task['res']
                    );
                }
            }

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 查出添加用户项目信息
     * @return mixed|void
     */
    public function addPhotographerWork()
    {
        $this->notPhotographerIdentityVerify();
        $photographer_work = $this->_photographer(null, $this->guard)->photographerWorks()->where(
            ['status' => 0]
        )->first();
        if ($photographer_work) {
            $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                PhotographerWorkTag::allowFields()
            )->get()->toArray();
            $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
            $photographer_work = ArrServer::toNullStrData(
                $photographer_work,
                ['sheets_number', 'shooting_duration']
            );
            $photographer_work['tags'] = $photographer_work_tags;
        } else {
            $photographer_work = [];
            foreach (PhotographerWork::allowFields() as $v) {
                $photographer_work[$v] = '';
            }
            $photographer_work['tags'] = [];
        }
        $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);

        return $this->responseParseArray($photographer_work);
    }

    /**
     * 保存添加用户项目信息
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function addPhotographerWorkStore(PhotographerRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $asynchronous_task = [];
        \DB::beginTransaction();//开启事务
        try {
            $photographer = $this->_photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(
                ['status' => 0]
            )->first();
            if (!$photographer_work) {
                $photographer_work = PhotographerWork::create();
                $photographer_work->photographer_id = $photographer->id;
                $photographer_work->save();
            }
            $scene = '1/'.$photographer_work->id;
            if (!$photographer_work->xacode) {
                $xacode_res = WechatServer::generateXacode($scene, false);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer_work->xacode = $xacode_res['xacode'];
            }
            if (!$photographer_work->xacode_hyaline) {
                $xacode_res = WechatServer::generateXacode($scene);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographer_work->xacode_hyaline = $xacode_res['xacode'];
            }
            //  如果一旦填些客户行业 ，就算是一个商业项目
            $is_business = $request->is_business;
            if ($request->photographer_work_customer_industry_id){
                $is_business = 1;
            }
            $photographer_work->name = $request->name;
            $photographer_work->describe = $request->describe;
            $photographer_work->is_business = $is_business;
            $photographer_work->location = $request->location;
            $photographer_work->address = $request->address;
            $photographer_work->latitude = $request->latitude;
            $photographer_work->longitude = $request->longitude;
            $photographer_work->customer_name = $request->customer_name;
            $photographer_work->photographer_work_customer_industry_id = $request->photographer_work_customer_industry_id;
            $photographer_work->project_amount = $request->project_amount;
            $photographer_work->hide_project_amount = $request->hide_project_amount;
            $photographer_work->sheets_number = $request->sheets_number;
            $photographer_work->hide_sheets_number = $request->hide_sheets_number;
            $photographer_work->shooting_duration = $request->shooting_duration;
            $photographer_work->hide_shooting_duration = $request->hide_shooting_duration;
            $photographer_work->photographer_work_category_id = $request->photographer_work_category_id;
            $photographer_work->status = 200;
            $photographer_work->save();
            $photographerWorkSources = $photographer_work->photographerWorkSources()->where(
                ['status' => 200]
            )->orderBy('sort', 'asc')->get();
            if ($photographerWorkSources) {
                foreach ($photographerWorkSources as $photographerWorkSource) {
                    if ($photographerWorkSource->type == 'image') {
                        $photographerWorkSource->is_new_source = 0;
                        $photographerWorkSource->save();
                        $asynchronous_task[] = [
                            'task_type' => 'editRunGenerateWatermark',
                            'photographer_work_source_id' => $photographerWorkSource->id,
                            'edit_node' => '添加项目',
                        ];
                    }
                }
            }
            PhotographerWorkTag::where(['photographer_work_id' => $photographer_work->id])->delete();
            if ($request->tags) {
                foreach ($request->tags as $v) {
                    $photographer_work_tag = PhotographerWorkTag::create();
                    $photographer_work_tag->photographer_work_id = $photographer_work->id;
                    $photographer_work_tag->name = $v;
                    $photographer_work_tag->save();
                }
            }
            \DB::commit();//提交事务
            foreach ($asynchronous_task as $task) {
                if ($task['task_type'] == 'editRunGenerateWatermark') {
                    PhotographerWorkSource::editRunGenerateWatermark(
                        $task['photographer_work_source_id'],
                        $task['edit_node']
                    );
                } elseif ($task['task_type'] == 'error_qiniuNotifyFop') {
                    ErrLogServer::qiniuNotifyFop(
                        $task['step'],
                        $task['msg'],
                        $task['request_data'],
                        $task['photographerWorkSource'],
                        $task['res']
                    );
                }
            }

            return $this->responseParseArray(['photographer_work_id' => $photographer_work->id]);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }
}
