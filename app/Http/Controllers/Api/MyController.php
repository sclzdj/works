<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\PhotographerRequest;
use App\Http\Requests\Index\UserRequest;
use App\Jobs\AsyncDocPdfMakeJob;
use App\Libs\WXBizDataCrypt\WXBizDataCrypt;
use App\Model\Index\AsyncDocPdfMake;
use App\Model\Index\DocPdf;
use App\Model\Index\DocPdfPhotographerWork;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\RandomPhotographer;
use App\Model\Index\User;
use App\Model\Index\ViewRecord;
use App\Model\Index\Visitor;
use App\Servers\ArrServer;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use App\Servers\WechatServer;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

/**
 * 我的相关
 * Class MyController
 * @package App\Http\Controllers\Api
 */
class MyController extends UserGuardController
{
    /**
     * 用户信息保存
     *
     * @param UserRequest $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function saveInfo(UserRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $user = auth($this->guard)->user();
            $appid = config('custom.wechat.mp.appid');
            $sessionKey = $user->session_key;
            $encryptedData = $request->encryptedData;
            $iv = $request->iv;
            $pc = new WXBizDataCrypt($appid, $sessionKey);
            $errCode = $pc->decryptData($encryptedData, $iv, $data);
            if ($errCode == 0) {
                $data = json_decode($data, true);
                if ($data['openId'] == $user->openid) {
                    if (isset($data['unionId']) && $data['unionId'] != '') {
                        $user->nickname = $data['nickName'];
                        if ($data['avatarUrl']) {
                            $avatar = '';
                            $bucket = 'zuopin';
                            $buckets = config('custom.qiniu.buckets');
                            $domain = $buckets[$bucket]['domain'] ?? '';
                            //用于签名的公钥和私钥
                            $accessKey = config('custom.qiniu.accessKey');
                            $secretKey = config('custom.qiniu.secretKey');
                            // 初始化签权对象
                            $auth = new Auth($accessKey, $secretKey);
                            $bucketManager = new BucketManager($auth);
                            list($ret, $err) = $bucketManager->fetch($data['avatarUrl'], $bucket);
                            if ($err) {
                                \DB::rollback();//回滚事务

                                return $this->response->error($err->message(), 500);
                            } else {
                                $avatar = $domain.'/'.$ret['key'];
                            }
                            $user->avatar = $avatar;
                        }
                        $user->gender = $data['gender'];
                        $user->country = $data['country'];
                        $user->province = $data['province'];
                        $user->unionid = $data['unionId'];
                        $user->city = $data['city'];
                        $user->is_wx_authorize = 1;
                        $user->save();
                        \DB::commit();//提交事务

                        return $this->response->noContent();
                    } else {
                        \DB::rollback();//回滚事务

                        return $this->response->error('unionId未获取到', 500);
                    }
                } else {
                    \DB::rollback();//回滚事务

                    return $this->response->error('openID校验错误', 500);
                }
            } else {
                \DB::rollback();//回滚事务

                return $this->response->error('微信解密错误：'.$errCode, 500);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 用户信息保存
     *
     * @param UserRequest $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function saveMobile(UserRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $user = auth($this->guard)->user();
            $appid = config('custom.wechat.mp.appid');
            $sessionKey = $user->session_key;
            $encryptedData = $request->encryptedData;
            $iv = $request->iv;
            $pc = new WXBizDataCrypt($appid, $sessionKey);
            $errCode = $pc->decryptData($encryptedData, $iv, $data);
            if ($errCode == 0) {
                $data = json_decode($data, true);
                if ($data['openId'] == $user->openid) {
                    $user->phoneNumber = $data['phoneNumber'];
                    $user->purePhoneNumber = $data['purePhoneNumber'];
                    $user->countryCode = $data['countryCode'];
                    $user->is_wx_get_phone_number = 1;
                    $user->save();
                    \DB::commit();//提交事务

                    return $this->response->noContent();
                } else {
                    \DB::rollback();//回滚事务

                    return $this->response->error('openID校验错误', 500);
                }
            } else {
                \DB::rollback();//回滚事务

                return $this->response->error('微信解密错误：'.$errCode, 500);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 刷新token
     *
     * @return mixed
     */
    public function refresh()
    {
        return $this->respondWithToken(auth($this->guard)->refresh());
    }

    /**
     * 退出
     *
     * @return \Dingo\Api\Http\Response
     */
    public function logout()
    {
        auth($this->guard)->logout();

        return $this->response()->noContent();
    }

    /**
     * 我的资料
     *
     * @return \Dingo\Api\Http\Response
     */
    public function info()
    {
        $info = auth($this->guard)->user()->toArray();
        $info = ArrServer::inData($info, User::allowFields());

        return $this->responseParseArray($info);
    }

    /**
     * 用户身份
     * @return mixed
     */
    public function identity()
    {
        $info = auth($this->guard)->user();

        return $this->responseParseArray(
            [
                'identity' => $info->identity,
                'is_wx_authorize' => $info->is_wx_authorize,
                'is_wx_get_phone_number' => $info->is_wx_get_phone_number,
                'is_formal_photographer' => $info->is_formal_photographer,
            ]
        );
    }

    /**
     * 我的摄影师信息
     *
     * @return \Dingo\Api\Http\Response
     */
    public function photographerInfo()
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer = SystemServer::parseRegionName($photographer);
        $photographer = SystemServer::parsePhotographerRank($photographer);

        return $this->responseParseArray($photographer);
    }

    /**
     * 我的摄影师作品集列表
     * @param UserRequest $request
     */
    public function photographerWorks(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $keywords = $request->keywords;
        $whereRaw = '1';
        if (!empty($keywords)) {
            $whereRaw = "(photographer_works.customer_name like '%{$keywords}%' || photographer_work_customer_industries.name like '%{$keywords}%' || photographer_work_categories.name like '%{$keywords}%' || photographer_work_tags.name like '%{$keywords}%')";
        }
        $photographer_works = $photographer->photographerWorks()->select('photographer_works.*')->join(
            'photographer_work_customer_industries',
            'photographer_works.photographer_work_customer_industry_id',
            '=',
            'photographer_work_customer_industries.id'
        )->join(
            'photographer_work_categories',
            'photographer_works.photographer_work_category_id',
            '=',
            'photographer_work_categories.id'
        );
        if (!empty($keywords)) {
            $photographer_works = $photographer_works->join(
                'photographer_work_tags',
                'photographer_work_tags.photographer_work_id',
                '=',
                'photographer_works.id'
            );
        }
        $photographer_works = $photographer_works->where(['photographer_works.status' => 200])->whereRaw(
            $whereRaw
        )->orderBy(
            'photographer_works.roof',
            'desc'
        )->orderBy(
            'photographer_works.created_at',
            'desc'
        )->paginate(
            $request->pageSize
        );
        $all_tags = [];
        foreach ($photographer_works as $k => $photographer_work) {
            $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                PhotographerWorkTag::allowFields()
            )->get()->toArray();
            $all_tags[] = $photographer_work_tags;
        }
        $photographer_works = SystemServer::parsePaginate($photographer_works->toArray());
        $photographer_works['data'] = ArrServer::inData($photographer_works['data'], PhotographerWork::allowFields());
        foreach ($photographer_works['data'] as $k => $v) {
            $photographer_works['data'][$k]['tags'] = $all_tags[$k];
        }
        $photographer_works['data'] = ArrServer::toNullStrData(
            $photographer_works['data'],
            ['project_amount', 'sheets_number', 'shooting_duration']
        );
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCover($photographer_works['data']);
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_works['data']);
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCategory($photographer_works['data']);

        return $this->response->array($photographer_works);
    }

    /**
     * 我的摄影师作品集详情
     * @param UserRequest $request
     */
    public function photographerWork(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer = User::photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        if ($photographer_work->photographer_id != $photographer->id) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
            PhotographerWorkSource::allowFields()
        )->where('status', 200)->orderBy('sort', 'asc')->get()->toArray();
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
        $photographer_work = ArrServer::toNullStrData(
            $photographer_work,
            ['project_amount', 'sheets_number', 'shooting_duration']
        );
        $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
        $photographer_work['sources'] = $photographer_work_sources;
        $photographer_work['tags'] = $photographer_work_tags;
        $photographer_work['photographer'] = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer_work['photographer'] = SystemServer::parseRegionName($photographer_work['photographer']);
        $photographer_work['photographer'] = SystemServer::parsePhotographerRank($photographer_work['photographer']);

        return $this->response->array($photographer_work);
    }

    /**
     * 我的摄影师作品资源列表
     * @param UserRequest $request
     */
    public function photographerWorkSources(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $fields = array_map(
            function ($v) {
                return 'photographer_work_sources.'.$v;
            },
            PhotographerWorkSource::allowFields()
        );
        $photographerWorkSources = PhotographerWorkSource::select(
            $fields
        )->join(
            'photographer_works',
            'photographer_work_sources.photographer_work_id',
            '=',
            'photographer_works.id'
        )->where(
            [
                'photographer_works.photographer_id' => $photographer->id,
                'photographer_work_sources.status' => 200,
                'photographer_works.status' => 200,
                'photographer_work_sources.type' => 'image',
            ]
        )->orderBy(
            'photographer_works.roof',
            'desc'
        )->orderBy(
            'photographer_works.created_at',
            'desc'
        )->orderBy(
            'photographer_work_sources.sort',
            'asc'
        )->paginate(
            $request->pageSize
        );
        $photographerWorkSources = SystemServer::parsePaginate($photographerWorkSources->toArray());

        return $this->response->array($photographerWorkSources);
    }

    /**
     * 我的摄影师统计信息
     * @param UserRequest $request
     * @return mixed|void
     */
    public function photographerStatistics(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $rankListLast = $request->rankListLast ?? 50;
        $photographer = User::photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $photographer_work_count = PhotographerWork::where(
            ['photographer_id' => $photographer->id, 'status' => 200]
        )->count();
        $visitor_count = Visitor::where(
            ['photographer_id' => $photographer->id]
        )->count();
        $view_record_count = ViewRecord::where(
            ['photographer_id' => $photographer->id]
        )->count();
        $today = date('Y-m-d').' 00:00:00';
        $sql = "SELECT photographers.id,(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id` AND `created_at`>='{$today}') AS `visitor_today_count`,(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id`) AS `visitor_count` FROM `photographers` WHERE `photographers`.`status`=200 ORDER BY `visitor_today_count` DESC,`visitor_count` DESC,`photographers`.`created_at` ASC";
        $photographers = \DB::select($sql, []);
        $myRank = 0;
        $photographer_count = 0;
        $visitor_today_count_rank_list_last = 0;
        $visitor_count_rank_list_last = 0;
        $visitor_today_count_my = 0;
        $visitor_count_my = 0;
        $visitor_today_count_differ = 0;
        $visitor_count_differ = 0;
        foreach ($photographers as $k => $p) {
            if ($photographer->id == $p->id) {
                $myRank = $k + 1;
                $visitor_today_count_my = $p->visitor_today_count;
                $visitor_count_my = $p->visitor_count;
            }
            if ($k == $rankListLast-1) {
                $visitor_today_count_rank_list_last = $p->visitor_today_count;
                $visitor_count_rank_list_last = $p->visitor_count;
            }
            $photographer_count++;
        }
        if ($photographer_count > $rankListLast) {
            $visitor_today_count_differ = $visitor_today_count_rank_list_last - $visitor_today_count_my;
            $visitor_count_differ = $visitor_count_rank_list_last - $visitor_count_my;
        }

        return $this->responseParseArray(
            compact(
                'photographer_work_count',
                'visitor_count',
                'view_record_count',
                'myRank',
                'visitor_today_count_differ',
                'visitor_count_differ'
            )
        );
    }

    /**
     * 删除我的摄影师作品集
     * @param UserRequest $request
     */
    public function photographerWorkDelete(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer = User::photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        if ($photographer_work->photographer_id != $photographer->id) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        \DB::beginTransaction();//开启事务
        try {
            $photographer_work->status = 400;
            $photographer_work->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存我的摄影师信息
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function savePhotographerInfo(PhotographerRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            //验证短信验证码
            $verify_result = SystemServer::verifySmsCode(
                $request->mobile,
                $request->sms_code,
                'update_my_photographer_info',
                $request->getClientIp()
            );
            if ($verify_result['status'] != 'SUCCESS') {
                \DB::rollback();//回滚事务

                return $this->response->error($verify_result['message'], 500);
            }
            $photographer = User::photographer(null, $this->guard);
            //验证手机号的唯一性
            $other_photographer = Photographer::where('id', '!=', $photographer->id)->where(
                ['mobile' => $request->mobile, 'status' => 200]
            )->first();
            if ($other_photographer) {
                \DB::rollback();//回滚事务

                return $this->response->error('该手机号已被注册成为摄影师了', 500);
            }
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographer->name = $request->name;
            $photographer->province = $request->province;
            $photographer->city = $request->city;
            $photographer->area = $request->area;
            $photographer->photographer_rank_id = $request->photographer_rank_id;
            $photographer->wechat = $request->wechat;
            $photographer->mobile = $request->mobile;
            $photographer->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存我的摄影师头像
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function savePhotographerAvatar(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographer->avatar = (string)$request->avatar;
            $photographer->save();
            $xacode = User::createXacode($photographer->id);
            $user = auth($this->guard)->user();
            $user->xacode = $xacode;
            $user->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存我的摄影师背景图片
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function savePhotographerBgImg(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographer->bg_img = (string)$request->bg_img;
            $photographer->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 置顶
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function setRoof(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographerWork = $photographer->photographerWorks()->where(
                ['id' => $request->photographer_work_id, 'status' => 200]
            )->first();
            if (!$photographerWork) {
                return $this->response->error('摄影师作品集不存在', 500);
            }
            $photographerWorks = $photographer->photographerWorks()->select(['id', 'roof'])->where(
                'roof',
                '>',
                0
            )->where('id', '!=', $request->photographer_work_id)->orderBy('roof', 'asc')->get();
            $roof = 1;
            foreach ($photographerWorks as $k => $tmp_photographerWork) {
                $tmp_photographerWork->roof = $k + 1;
                $tmp_photographerWork->save();
                $roof++;
            }
            $photographerWork->roof = $roof;
            $photographerWork->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 修改我的摄影师作品集
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function savePhotographerWorkInfo(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(
                ['id' => $request->photographer_work_id, 'status' => 200]
            )->first();
            if (!$photographer_work) {
                return $this->response->error('摄影师作品集不存在', 500);
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
                    $log_filename = 'logs/qiniu_fop_error/'.date('Y-m-d').'/'.date('H').'.log';
                    if ($photographer_work_source->type == 'image') {
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?imageInfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['code']) || $res['data']['code'] == 200) {
                                $photographer_work_source->size = $res['data']['size'];
                                $photographer_work_source->width = $res['data']['width'];
                                $photographer_work_source->height = $res['data']['height'];
                                $photographer_work_source->deal_size = $res['data']['size'];
                                $photographer_work_source->deal_width = $res['data']['width'];
                                $photographer_work_source->deal_height = $res['data']['height'];
                                $photographer_work_source->rich_size = $res['data']['size'];
                                $photographer_work_source->rich_width = $res['data']['width'];
                                $photographer_work_source->rich_height = $res['data']['height'];
                            } else {
                                ErrLogServer::QiniuNotifyFop(
                                    0,
                                    '七牛图片信息接口返回错误信息：'.json_encode($res['data']),
                                    $request->all(),
                                    $photographer_work_source
                                );
                            }
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                0,
                                '请求七牛图片信息接口报错：'.$res['msg'].' '.json_encode($res),
                                $request->all(),
                                $photographer_work_source
                            );
                        }
                    } elseif ($photographer_work_source->type == 'video') {
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?avinfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['code']) || $res['data']['code'] == 200) {
                                $photographer_work_source->size = $res['data']['format']['size'];
                                $photographer_work_source->deal_size = $res['data']['format']['size'];
                                $photographer_work_source->rich_size = $res['data']['format']['size'];
                                $photographer_work_source->save();
                            } else {
                                ErrLogServer::QiniuNotifyFop(
                                    0,
                                    '七牛视频信息接口返回错误信息：'.json_encode($res['data']),
                                    $request->all(),
                                    $photographer_work_source
                                );
                            }
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                0,
                                '请求七牛视频信息接口报错：'.$res['msg'].' '.json_encode($res),
                                $request->all(),
                                $photographer_work_source
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
                                '持久化请求失败：'.json_encode($qrst['err']),
                                $request->all(),
                                $photographer_work_source
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
     * 随机摄影师列表
     * @return mixed|void
     */
    public function randomPhotographers()
    {
        $this->notVisitorIdentityVerify();
        $user = auth($this->guard)->user();
        $random = config('custom.photographer.random');
        \DB::beginTransaction();//开启事务
        try {
            $randomPhotographers = RandomPhotographer::where('user_id', $user->id)->take($random)->get();
            $photographers = [];
            foreach ($randomPhotographers as $randomPhotographer) {
                $photographer = Photographer::select(Photographer::allowFields())->where(
                    ['id' => $randomPhotographer->photographer_id, 'status' => 200]
                )->first();
                if ($photographer) {
                    $photographers[] = $photographer->toArray();
                } else {
                    $randomPhotographer->delete();
                }
            }
            $differ = $random - count($photographers);
            if ($differ > 0) {
                for ($i = 0; $i < $differ; $i++) {
                    $where = ['status' => 200];
                    $total = Photographer::where($where)->whereNotIn('id', ArrServer::ids($photographers))->count();
                    if ($total > 0) {
                        $skip = mt_rand(0, $total - 1);
                        $photographer = Photographer::where($where)->whereNotIn(
                            'id',
                            ArrServer::ids($photographers)
                        )->select(
                            Photographer::allowFields()
                        )->skip($skip)->take(1)->first();
                        if ($photographer) {
                            $randomPhotographer = RandomPhotographer::create();
                            $randomPhotographer->user_id = $user->id;
                            $randomPhotographer->photographer_id = $photographer->id;
                            $randomPhotographer->save();
                            $photographers[] = $photographer->toArray();
                        } else {
                            break;
                        }
                    }

                }
            }
            if ($photographers) {
                $fields = array_map(
                    function ($v) {
                        return 'photographer_work_sources.'.$v;
                    },
                    PhotographerWorkSource::allowFields()
                );
                foreach ($photographers as $k => $photographer) {
                    $photographer_work_sources = PhotographerWorkSource::join(
                        'photographer_works',
                        'photographer_work_sources.photographer_work_id',
                        '=',
                        'photographer_works.id'
                    )->select($fields)
                        ->where(
                            [
                                'photographer_works.status' => 200,
                                'photographer_work_sources.status' => 200,
                                'photographer_works.photographer_id' => $photographer['id'],
                                'photographer_work_sources.type' => 'image',
                            ]
                        )
                        ->orderBy('photographer_work_sources.created_at', 'desc')->take(3)->get()->toArray();
                    $photographers[$k]['photographer_work_sources'] = $photographer_work_sources;
                }
                $photographers = SystemServer::parseRegionName($photographers);
                $photographers = SystemServer::parsePhotographerRank($photographers);
            }
            \DB::commit();//提交事务

            return $this->responseParseArray($photographers);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 人脉排行榜
     * @param UserRequest $request
     * @return mixed
     */
    public function rankingList(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $limit = $request->limit ?? 50;
        $photographer = User::photographer(null, $this->guard);
        $fields = array_map(
            function ($v) {
                return "`photographers`.`{$v}`";
            },
            Photographer::allowFields()
        );
        $fields = implode(',', $fields);
        $today = date('Y-m-d').' 00:00:00';
        $sql = "SELECT {$fields},(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id` AND `created_at`>='{$today}') AS `visitor_today_count`,(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id`) AS `visitor_count` FROM `photographers` WHERE `photographers`.`status`=200 ORDER BY `visitor_today_count` DESC,`visitor_count` DESC,`photographers`.`created_at` ASC LIMIT {$limit}";
        $photographers = \DB::select($sql, []);
        $myRank = 0;
        $_fields = array_map(
            function ($v) {
                return 'photographer_work_sources.'.$v;
            },
            PhotographerWorkSource::allowFields()
        );
        foreach ($photographers as $k => $p) {
            if ($photographer->id == $p->id) {
                $myRank = $k + 1;
            }
            $photographers[$k] = json_decode(json_encode($p), true);

            $photographer_work_sources = PhotographerWorkSource::join(
                'photographer_works',
                'photographer_work_sources.photographer_work_id',
                '=',
                'photographer_works.id'
            )->select($_fields)
                ->where(
                    [
                        'photographer_works.status' => 200,
                        'photographer_work_sources.status' => 200,
                        'photographer_works.photographer_id' => $p->id,
                        'photographer_work_sources.type' => 'image',
                    ]
                )
                ->orderBy('photographer_work_sources.created_at', 'desc')->take(3)->get()->toArray();
            $photographers[$k]['photographer_work_sources'] = $photographer_work_sources;
        }
        $photographers = SystemServer::parseRegionName($photographers);
        $photographers = SystemServer::parsePhotographerRank($photographers);
        $response = [];
        $response['myRank'] = $myRank;
        $response['data'] = $photographers;

        return $this->response->array($response);
    }

    /**
     * 获取我的浏览摄影师记录
     * @param UserRequest $request
     * @return mixed
     */
    public function viewRecords(UserRequest $request)
    {
        $user = auth($this->guard)->user();
        $fields = array_map(
            function ($v) {
                return 'photographers.'.$v;
            },
            Photographer::allowFields()
        );
        $view_records = ViewRecord::join(
            'photographers',
            'view_records.photographer_id',
            '=',
            'photographers.id'
        )->select(
            $fields
        )->where(
            ['view_records.user_id' => $user->id, 'view_records.is_newest' => 1, 'photographers.status' => 200]
        )->orderBy(
            'view_records.created_at',
            'desc'
        )->paginate(
            $request->pageSize
        );
        $view_records = SystemServer::parsePaginate($view_records->toArray());
        $view_records['data'] = SystemServer::parseRegionName($view_records['data']);
        if ($view_records['data']) {
            $fields = array_map(
                function ($v) {
                    return 'photographer_work_sources.'.$v;
                },
                PhotographerWorkSource::allowFields()
            );
            foreach ($view_records['data'] as $k => $photographer) {
                $photographer_work_sources = PhotographerWorkSource::join(
                    'photographer_works',
                    'photographer_work_sources.photographer_work_id',
                    '=',
                    'photographer_works.id'
                )->select($fields)
                    ->where(
                        [
                            'photographer_works.status' => 200,
                            'photographer_work_sources.status' => 200,
                            'photographer_works.photographer_id' => $photographer['id'],
//                            'photographer_work_sources.type' => 'image',
                        ]
                    )
                    ->orderBy('photographer_work_sources.created_at', 'desc')->take(3)->get()->toArray();
                $view_records['data'][$k]['photographer_work_sources'] = $photographer_work_sources;
            }
            $view_records['data'] = SystemServer::parsePhotographerRank($view_records['data']);
        }

        return $this->response->array($view_records);
    }

    /**
     * 保存pdf
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function saveDocPdf(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        \DB::beginTransaction();//开启事务
        try {
            $doc_pdf = DocPdf::create();
            $doc_pdf->photographer_id = $photographer->id;
            $doc_pdf->name = $request->name;
            $doc_pdf->estimate_completion_time = 60;//需要处理
            foreach ($request->photographer_work_ids as $k => $photographer_work_id) {
                $doc_pdf_photographer_work = DocPdfPhotographerWork::create();
                $doc_pdf_photographer_work->doc_pdf_id = $doc_pdf->id;
                $doc_pdf_photographer_work->photographer_work_id = $photographer_work_id;
                $doc_pdf_photographer_work->sort = $k;
                $doc_pdf_photographer_work->save();
            }
            $doc_pdf->save();
            $asyncDocPdfMake = AsyncDocPdfMake::create();
            $asyncDocPdfMake->user_id = auth($this->guard)->id();
            $asyncDocPdfMake->doc_pdf_id = $doc_pdf->id;
            $asyncDocPdfMake->save();
            \DB::commit();//提交事务
            AsyncDocPdfMakeJob::dispatch($asyncDocPdfMake);

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * pdf列表
     * @param UserRequest $request
     * @return mixed
     */
    public function docPdfs(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $doc_pdfs = DocPdf::select(DocPdf::allowFields())->where('photographer_id', $photographer->id)->whereIn(
            'status',
            [0, 200]
        )->paginate(
            $request->pageSize
        );
        $doc_pdfs = SystemServer::parsePaginate($doc_pdfs->toArray());
        $now_time = time();
        foreach ($doc_pdfs['data'] as $k => $doc_pdf) {
            if ($doc_pdf['status'] == 0) {
                $doc_pdfs['data'][$k]['residue_estimate_completion_time'] = $doc_pdf['estimate_completion_time'] - ($now_time - strtotime(
                            $doc_pdf['created_at']
                        ));
                $doc_pdfs['data'][$k]['residue_estimate_completion_time'] = max(
                    0,
                    $doc_pdfs['data'][$k]['residue_estimate_completion_time']
                );
            } else {
                $doc_pdfs['data'][$k]['residue_estimate_completion_time'] = 0;
            }
        }

        return $this->response->array($doc_pdfs);
    }

    /**
     * 获取pdf当前状态
     * @param UserRequest $request
     * @return mixed|void
     */
    public function getDocPdfStatus(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $doc_pdf = DocPdf::select(DocPdf::allowFields())->where(
            ['photographer_id' => $photographer->id, 'doc_pdf_id' => $request->doc_pdf_id]
        )->first();
        if (!$doc_pdf) {
            return $this->response->error('PDF不存在', 500);
        }

        return $this->responseParseArray(['status' => $doc_pdf->status]);
    }

    /**
     * 获取pdf当前状态
     * @param UserRequest $request
     * @return mixed|void
     */
    public function docPdfDelete(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $doc_pdf = DocPdf::select(DocPdf::allowFields())->where(
            ['photographer_id' => $photographer->id, 'doc_pdf_id' => $request->doc_pdf_id]
        )->first();
        if (!$doc_pdf) {
            return $this->response->error('PDF不存在', 500);
        }
        \DB::beginTransaction();//开启事务
        try {
            $doc_pdf->status = 400;
            $doc_pdf->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }
}
