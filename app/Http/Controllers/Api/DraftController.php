<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\PhotographerRequest;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\User;
use App\Servers\ArrServer;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
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
     * 查出摄影师注册作品集资源
     * @return mixed|void
     */
    public function registerPhotographerWorkSource()
    {
        $this->notVisitorIdentityVerify();
        $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(['status' => 0])->first(
        );
        $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
            PhotographerWorkSource::allowFields()
        )->where('status', 200)->orderBy('sort', 'asc')->get()->toArray();

        return $this->responseParseArray($photographer_work_sources);
    }

    /**
     * 保存摄影师注册作品集资源
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerWorkSourceStore(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(
                ['status' => 0]
            )->first();
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
                    $photographer_work_source->deal_key = $v['key'];
                    $photographer_work_source->deal_url = $v['url'];
                    $photographer_work_source->rich_key = $v['key'];
                    $photographer_work_source->rich_url = $v['url'];
                    $photographer_work_source->type = $v['type'];
                    $photographer_work_source->origin = $v['origin'];
                    $photographer_work_source->sort = $k + 1;
                    $photographer_work_source->status = 200;
                    $photographer_work_source->save();
                    if ($photographer_work_source->type == 'image') {
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?imageInfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                                $photographer_work_source->size = $res['data']['size'];
                                $photographer_work_source->width = $res['data']['width'];
                                $photographer_work_source->height = $res['data']['height'];
                                $photographer_work_source->deal_size = $res['data']['size'];
                                $photographer_work_source->deal_width = $res['data']['width'];
                                $photographer_work_source->deal_height = $res['data']['height'];
                                $photographer_work_source->rich_size = $res['data']['size'];
                                $photographer_work_source->rich_width = $res['data']['width'];
                                $photographer_work_source->rich_height = $res['data']['height'];
                                $photographer_work_source->save();
                            } else {
                                ErrLogServer::QiniuNotifyFop(
                                    0,
                                    '七牛图片信息接口返回错误信息',
                                    $request->all(),
                                    $photographer_work_source,
                                    $res['data']
                                );
                            }
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                0,
                                '请求七牛图片信息接口报错：'.$res['msg'],
                                $request->all(),
                                $photographer_work_source,
                                $res
                            );
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
                                ErrLogServer::QiniuNotifyFop(
                                    0,
                                    '七牛视频信息接口返回错误信息',
                                    $request->all(),
                                    $photographer_work_source,
                                    $res['data']
                                );
                            }
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                0,
                                '请求七牛视频信息接口报错：'.$res['msg'],
                                $request->all(),
                                $photographer_work_source,
                                $res
                            );
                        }
                    }
                }
            }
            $photographer_work->photographerWorkSources()->where(['status' => 300])->update(['status' => 400]);
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 查出摄影师注册作品集信息
     * @return mixed|void
     */
    public function registerPhotographerWork()
    {
        $this->notVisitorIdentityVerify();
        $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(['status' => 0])->first(
        );
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
        $photographer_work = ArrServer::toNullStrData(
            $photographer_work,
            ['project_amount', 'sheets_number', 'shooting_duration']
        );
        $photographer_work['tags'] = $photographer_work_tags;
        $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);

        return $this->responseParseArray($photographer_work);
    }

    /**
     * 保存摄影师注册作品集信息
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerWorkStore(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(
                ['status' => 0]
            )->first();
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
     * 查出摄影师注册信息
     * @return mixed|void
     */
    public function registerPhotographer()
    {
        $this->notVisitorIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer = SystemServer::parseRegionName($photographer);
        $photographer = SystemServer::parsePhotographerRank($photographer);

        return $this->responseParseArray($photographer);

    }

    /**
     * 保存摄影师注册信息
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerStore(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
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
            $photographer = User::photographer(null, $this->guard);
            //验证手机号的唯一性
            $other_photographer = Photographer::where('id', '!=', $photographer->id)->where(
                ['mobile' => $request->mobile, 'status' => 200]
            )->first();
            if ($other_photographer) {
                \DB::rollback();//回滚事务

                return $this->response->error('该手机号已被注册成为摄影师了', 500);
            }
            if ($request->avatar) {
                $photographer->avatar = (string)$request->avatar;
                $xacode = User::createXacode($photographer->id);
                $user = auth($this->guard)->user();
                $user->xacode = $xacode;
            } else {
                $photographer->avatar = $user->avatar;
            }
            $photographer->name = $request->name;
            $photographer->province = $request->province;
            $photographer->city = $request->city;
            $photographer->area = $request->area;
            $photographer->photographer_rank_id = $request->photographer_rank_id;
            $photographer->wechat = $request->wechat;
            $photographer->mobile = $request->mobile;
            $photographer->status = 200;
            $photographer->save();
            $photographer_work = $photographer->photographerWorks()->where(['status' => 0])->first();
            if (!$photographer_work) {
                \DB::rollback();//回滚事务

                return $this->response->error('作品集不存在', 500);
            }
            $photographerWorkSources = $photographer_work->photographerWorkSources()->where(
                ['status' => 200]
            )->orderBy('sort', 'asc')->get();
            if ($photographerWorkSources) {
                foreach ($photographerWorkSources as $photographerWorkSource) {
                    if ($photographerWorkSource->type == 'image') {
                        $fops = ["imageMogr2/thumbnail/1200x|imageMogr2/colorspace/srgb|imageslim"];
                        $bucket = 'zuopin';
                        $qrst = SystemServer::qiniuPfop(
                            $bucket,
                            $photographerWorkSource->key,
                            $fops,
                            null,
                            config(
                                'app.url'
                            ).'/api/notify/qiniu/fop?photographer_work_source_id='.$photographerWorkSource->id.'&step=1',
                            true
                        );
                        if ($qrst['err']) {
                            ErrLogServer::QiniuNotifyFop(
                                0,
                                '七牛持久化接口返回错误信息',
                                $request->all(),
                                $photographerWorkSource,
                                $qrst['err']
                            );
                        }
                    }
                }
            }
            $photographer_work->status = 200;
            $photographer_work->save();
            $user->identity = 1;
            $user->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 查出添加摄影师作品集资源
     * @return mixed|void
     */
    public function addPhotographerWorkSource()
    {
        $this->notPhotographerIdentityVerify();
        $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(['status' => 0])->first(
        );
        if ($photographer_work) {
            $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
                PhotographerWorkSource::allowFields()
            )->where('status', 200)->orderBy('sort', 'asc')->get()->toArray();
        } else {
            $photographer_work_sources = [];
        }


        return $this->responseParseArray($photographer_work_sources);
    }

    /**
     * 保存添加摄影师作品集资源
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function addPhotographerWorkSourceStore(PhotographerRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
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
                    $photographer_work_source->deal_key = $v['key'];
                    $photographer_work_source->deal_url = $v['url'];
                    $photographer_work_source->rich_key = $v['key'];
                    $photographer_work_source->rich_url = $v['url'];
                    $photographer_work_source->type = $v['type'];
                    $photographer_work_source->origin = $v['origin'];
                    $photographer_work_source->sort = $k + 1;
                    $photographer_work_source->status = 200;
                    $photographer_work_source->save();
                    if ($photographer_work_source->type == 'image') {
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?imageInfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                                $photographer_work_source->size = $res['data']['size'];
                                $photographer_work_source->width = $res['data']['width'];
                                $photographer_work_source->height = $res['data']['height'];
                                $photographer_work_source->deal_size = $res['data']['size'];
                                $photographer_work_source->deal_width = $res['data']['width'];
                                $photographer_work_source->deal_height = $res['data']['height'];
                                $photographer_work_source->rich_size = $res['data']['size'];
                                $photographer_work_source->rich_width = $res['data']['width'];
                                $photographer_work_source->rich_height = $res['data']['height'];
                                $photographer_work_source->save();
                            } else {
                                ErrLogServer::QiniuNotifyFop(
                                    0,
                                    '七牛图片信息接口返回错误信息',
                                    $request->all(),
                                    $photographer_work_source,
                                    $res['data']
                                );
                            }
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                0,
                                '请求七牛图片信息接口报错：'.$res['msg'],
                                $request->all(),
                                $photographer_work_source,
                                $res
                            );
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
                                ErrLogServer::QiniuNotifyFop(
                                    0,
                                    '七牛视频信息接口返回错误信息',
                                    $request->all(),
                                    $photographer_work_source,
                                    $res['data']
                                );
                            }
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                0,
                                '请求七牛视频信息接口报错：'.$res['msg'],
                                $request->all(),
                                $photographer_work_source,
                                $res
                            );
                        }
                    }
                    if ($photographer_work_source->type == 'image') {
                        $fops = ["imageMogr2/thumbnail/1200x|imageMogr2/colorspace/srgb|imageslim"];
                        $bucket = 'zuopin';
                        $qrst = SystemServer::qiniuPfop(
                            $bucket,
                            $photographer_work_source->key,
                            $fops,
                            null,
                            config(
                                'app.url'
                            ).'/api/notify/qiniu/fop?photographer_work_source_id='.$photographer_work_source->id.'&step=1',
                            true
                        );
                        if ($qrst['err']) {
                            ErrLogServer::QiniuNotifyFop(
                                0,
                                '七牛持久化接口返回错误信息',
                                $request->all(),
                                $photographer_work_source,
                                $qrst['err']
                            );
                        }
                    }

                }
            }
            $photographer_work->photographerWorkSources()->where(['status' => 300])->update(['status' => 400]);
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 查出添加摄影师作品集信息
     * @return mixed|void
     */
    public function addPhotographerWork()
    {
        $this->notPhotographerIdentityVerify();
        $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(['status' => 0])->first(
        );
        if ($photographer_work) {
            $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                PhotographerWorkTag::allowFields()
            )->get()->toArray();
            $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
            $photographer_work = ArrServer::toNullStrData(
                $photographer_work,
                ['project_amount', 'sheets_number', 'shooting_duration']
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
     * 保存添加摄影师作品集信息
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function addPhotographerWorkStore(PhotographerRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(
                ['status' => 0]
            )->first();
            if (!$photographer_work) {
                $photographer_work = PhotographerWork::create();
                $photographer_work->photographer_id = $photographer->id;
            }
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

            return $this->responseParseArray(['photographer_work_id' => $photographer_work->id]);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }
}
