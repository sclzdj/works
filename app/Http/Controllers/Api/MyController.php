<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\PhotographerRequest;
use App\Http\Requests\Index\UserRequest;
use App\Jobs\AsyncDocPdfMakeJob;
use App\Jobs\AsynchronousTask;
use App\Jobs\CheckImgSecurity;
use App\Libs\WXBizDataCrypt\WXBizDataCrypt;
use App\Model\Admin\SystemConfig;
use App\Model\Index\AsyncBaiduWorkSourceUpload;
use App\Model\Index\AsyncDocPdfMake;
use App\Model\Index\DocPdf;
use App\Model\Index\DocPdfPhotographerWork;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerGather;
use App\Model\Index\PhotographerGatherFilterRecord;
use App\Model\Index\PhotographerGatherWork;
use App\Model\Index\PhotographerInfoTag;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkCategory;
use App\Model\Index\PhotographerWorkCustomerIndustry;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\QiniuPfopRichSourceJob;
use App\Model\Index\Question;
use App\Model\Index\RandomPhotographer;
use App\Model\Index\User;
use App\Model\Index\UserGrowths;
use App\Model\Index\ViewRecord;
use App\Model\Index\Visitor;
use App\Servers\ArrServer;
use App\Servers\ErrLogServer;
use App\Servers\PhotographerServer;
use App\Servers\SystemServer;
use App\Servers\WechatServer;
use DB;
use Illuminate\Http\Request;
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
     * 微信用户信息保存
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
            $photographer = $this->_photographer(null, $this->guard);
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
                        if (!WechatServer::checkContentSecurity($user->nickname)){
                            return $this->response->error("用户名称带有非法字符！", 500);
                        }
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
                            $photographer->avatar = $avatar;
                        }
                        $photographer->name = $data['nickName'];
                        $user->gender = $data['gender'];
                        $user->country = $data['country'];
                        $user->province = $data['province'];
                        $user->unionid = $data['unionId'];
                        $user->city = $data['city'];
                        $user->is_wx_authorize = 1;
                        $photographer->save();
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
     * 微信用户信息保存
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
            $photographer = $this->_photographer(null, $this->guard);
            $appid = config('custom.wechat.mp.appid');
            $sessionKey = $user->session_key;
            $encryptedData = $request->encryptedData;
            $iv = $request->iv;
            $pc = new WXBizDataCrypt($appid, $sessionKey);
            $errCode = $pc->decryptData($encryptedData, $iv, $data);
            if ($errCode == 0) {
                $data = json_decode($data, true);
                $user->phoneNumber = $data['phoneNumber'];
                $user->purePhoneNumber = $data['purePhoneNumber'];
                $user->countryCode = $data['countryCode'];
                $user->is_wx_get_phone_number = 1;
                $photographer->mobile = $data['purePhoneNumber'];
                $photographer->save();
                $user->save();
                \DB::commit();//提交事务

                return $this->response->noContent();
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
     * 我的用户信息(统一班)
    */
    public function userinfo(){
        $info = auth($this->guard)->user()->toArray();
        $identity = [
            'identity' => $info['identity'],
            'is_wx_authorize' => $info['is_wx_authorize'],
            'is_wx_get_phone_number' => $info['is_wx_get_phone_number'],
            'is_formal_photographer' => $info['is_formal_photographer'],
        ];

        $info = ArrServer::inData($info, User::allowFields());

        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
        $photographer->updated_at = date('Y-m-d H:i:s');
        $photographer->save();
        User::where(['photographer_id' => $photographer->id])->update(['updated_at' => date('Y-m-d H:i:s')]);
        if (!$photographer || $photographer->status != 200) {
            $photographer = [
                'id' => $photographer->id
            ];
        }else{
            $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
            $photographer = SystemServer::parseRegionName($photographer);
            $photographer = SystemServer::parsePhotographerRank($photographer);
            $photographer['xacode'] = Photographer::getXacode($photographer['id'], false);
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

        }

        $data = [
            'info' => $info,
            'identity' => $identity,
            'photographer' => $photographer
        ];

        return $this->responseParseArray($data);
    }

    /**
     * 微信用户身份
     * @return mixed
     */
    public function identity()
    {
        $info = auth($this->guard)->user();
        $data = [
            'identity' => $info->identity,
            'is_wx_authorize' => $info->is_wx_authorize,
            'is_wx_get_phone_number' => $info->is_wx_get_phone_number,
            'is_formal_photographer' => $info->is_formal_photographer,
        ];
        $log = [
            'time' => date('Y-m-d H:i:s'),
            'user_id' => $info->id,
            'photographer_id' => $info->photographer_id,
            'response' => $data,
        ];
        SystemServer::filePutContents('logs/identity/'.date('Y-m-d').'.log', json_encode($log).PHP_EOL);

        return $this->responseParseArray($data);
    }

    /**
     * 我的用户信息
     *
     * @return \Dingo\Api\Http\Response
     */
    public function photographerInfo()
    {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer = SystemServer::parseRegionName($photographer);
        $photographer = SystemServer::parsePhotographerRank($photographer);
        $photographer['xacode'] = Photographer::getXacode($photographer['id'], false);
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
     * 我的用户项目id
     * @param UserRequest $request
     */
    public function photographerWorksIds(UserRequest $request){
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
//        $photographer = $this->_photographer(6674, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $photographer_works = $photographer->photographerWorks();
        $ids = [];
        if ($photographer_works){
            $ids = $photographer_works->get()->pluck('id');
        }

        return $this->responseParseArray($ids);
    }

    /**
     * 我的用户项目列表
     * @param UserRequest $request
     */
    public function photographerWorks(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $keywords = $request->keywords;
        if ($request->keywords !== null && $request->keywords !== '') {
            $whereRaw = "(`photographer_works`.`name` like ? || `photographer_works`.`customer_name` like ? || `photographer_work_customer_industries`.`name` like ? || `photographer_work_categories`.`name` like ? || EXISTS (select `photographer_work_tags`.* from `photographer_work_tags` where `photographer_work_tags`  .`photographer_work_id`=`photographer_works`.`id` AND `photographer_work_tags`.`name` like ?))";
            $whereRaw2 = ["%{$keywords}%", "%{$keywords}%", "%{$keywords}%", "%{$keywords}%", "%{$keywords}%"];
        }
        $photographer_works = $photographer->photographerWorks();
        $photographer_works2 = $photographer->photographerWorks();
        if ($request->photographer_work_category_ids !== null && $request->photographer_work_category_ids !== '') {
            $photographer_work_category_ids = explode(',', $request->photographer_work_category_ids);
            $exist_zero = in_array(0, $photographer_work_category_ids);
            $filter_photographer_work_category_ids = [];
            if ($exist_zero) {
                foreach ($photographer_work_category_ids as $k => $photographer_work_category_id) {
                    if ($photographer_work_category_id == 0) {
                        unset($photographer_work_category_ids[$k]);
                    }
                }
                $filter_photographer_work_category_ids[] = 0;
            }
            if ($photographer_work_category_ids) {
                $photographer_work_category_ids = implode(',', $photographer_work_category_ids);
                $photographerWorkCategories = PhotographerWorkCategory::whereRaw(
                    '(`id` in ('.$photographer_work_category_ids.') || `pid` in ('.$photographer_work_category_ids.') )'
                )->get();
                if ($photographerWorkCategories->count()) {
                    $photographer_work_category_ids = ArrServer::ids($photographerWorkCategories->toArray());
                    if ($photographer_work_category_ids) {
                        $filter_photographer_work_category_ids = array_merge(
                            $filter_photographer_work_category_ids,
                            $photographer_work_category_ids
                        );
                    }
                }
            }
            if ($filter_photographer_work_category_ids) {
                $photographer_works = $photographer_works->whereIn(
                    'photographer_works.photographer_work_category_id',
                    $filter_photographer_work_category_ids
                );
                $photographer_works2 = $photographer_works2->whereIn(
                    'photographer_works.photographer_work_category_id',
                    $filter_photographer_work_category_ids
                );
            }
        }
        if ($request->photographer_work_customer_industry_ids !== null && $request->photographer_work_customer_industry_ids !== '') {
            $photographer_work_customer_industry_ids = explode(',', $request->photographer_work_customer_industry_ids);
            $exist_zero = in_array(0, $photographer_work_customer_industry_ids);
            $filter_photographer_work_customer_industry_ids = [];
            if ($exist_zero) {
                foreach ($photographer_work_customer_industry_ids as $k => $photographer_work_customer_industry_id) {
                    if ($photographer_work_customer_industry_id == 0) {
                        unset($photographer_work_customer_industry_ids[$k]);
                    }
                }
                $filter_photographer_work_customer_industry_ids[] = 0;
            }
            if ($photographer_work_customer_industry_ids) {
                $photographer_work_customer_industry_ids = implode(',', $photographer_work_customer_industry_ids);
                $photographerWorkCustomerIndustries = PhotographerWorkCustomerIndustry::whereRaw(
                    '(`id` in ('.$photographer_work_customer_industry_ids.') || `pid` in ('.$photographer_work_customer_industry_ids.') )'
                )->get();
                if ($photographerWorkCustomerIndustries->count()) {
                    $photographer_work_customer_industry_ids = ArrServer::ids(
                        $photographerWorkCustomerIndustries->toArray()
                    );
                    if ($photographer_work_customer_industry_ids) {
                        $filter_photographer_work_customer_industry_ids = array_merge(
                            $filter_photographer_work_customer_industry_ids,
                            $photographer_work_customer_industry_ids
                        );
                    }
                }
            }
            if ($filter_photographer_work_customer_industry_ids) {
                $photographer_works = $photographer_works->whereIn(
                    'photographer_works.photographer_work_customer_industry_id',
                    $filter_photographer_work_customer_industry_ids
                );
                $photographer_works2 = $photographer_works2->whereIn(
                    'photographer_works.photographer_work_customer_industry_id',
                    $filter_photographer_work_customer_industry_ids
                );
            }
        }
        if ($request->is_business !== null && $request->is_business !== '') {
            $photographer_works = $photographer_works->where(
                ['photographer_works.is_business' => $request->is_business]
            );
        }

        if ($request->sheets_number_min !== null && $request->sheets_number_min !== '') {
            $photographer_works = $photographer_works->where(
                'photographer_works.sheets_number',
                '>=',
                $request->sheets_number_min
            );
        }
        if ($request->sheets_number_max !== null && $request->sheets_number_max !== '') {
            $photographer_works = $photographer_works->where(
                'photographer_works.sheets_number',
                '<=',
                $request->sheets_number_max
            );
        }
        if ($request->project_amount_min !== null && $request->project_amount_min !== '') {
            $photographer_works = $photographer_works->where(
                'photographer_works.project_amount',
                '>=',
                $request->project_amount_min
            );
        }
        if ($request->project_amount_max !== null && $request->project_amount_max !== '') {
            $photographer_works = $photographer_works->where(
                'photographer_works.project_amount',
                '<=',
                $request->project_amount_max
            );
        }
        if ($request->shooting_duration_min !== null && $request->shooting_duration_min !== '') {
            $photographer_works = $photographer_works->where(
                'photographer_works.shooting_duration',
                '>=',
                $request->shooting_duration_min
            );
        }
        if ($request->shooting_duration_max !== null && $request->shooting_duration_max !== '') {
            $photographer_works = $photographer_works->where(
                'photographer_works.shooting_duration',
                '<=',
                $request->shooting_duration_max
            );
        }
        if ($request->keywords !== null && $request->keywords !== '') {
            $photographer_works = $photographer_works->whereRaw(
                $whereRaw,
                $whereRaw2
            );
        }
        $raw = false;
        if ($request->sheets_number_null != null) {
            $photographer_works2 = $photographer_works2->whereNull('photographer_works.sheets_number');
        }
        if ($request->project_amount_null != null) {
            $photographer_works2 = $photographer_works2->whereNull('photographer_works.project_amount');
        }
        if ($request->shooting_duration_null != null) {
            $photographer_works2 = $photographer_works2->whereNull('photographer_works.shooting_duration');

        }

        if ($request->photographer_gather_id !== null && $request->photographer_gather_id > 0) {
            $photographer_work_ids = [];
            $photographerGather = PhotographerGather::where(
                ['id' => $request->photographer_gather_id, 'status' => 200]
            )->first();
            if ($photographerGather) {
                $photographerGatherWorks = PhotographerGatherWork::where(
                    ['photographer_gather_id' => $photographerGather->id]
                )->orderBy('sort', 'asc')->get()->toArray();
                if ($photographerGatherWorks) {
                    $photographer_work_ids = ArrServer::ids($photographerGatherWorks, 'photographer_work_id');
                    $photographer_works = $photographer_works->whereIn(
                        'photographer_works.id',
                        $photographer_work_ids
                    );
                } else {
                    $photographer_works = $photographer_works->where(['photographer_works.id' => 0]);
                }
            } else {
                $photographer_works = $photographer_works->where(['photographer_works.id' => 0]);
            }
        }
        $photographer_works = $photographer_works->where(['photographer_works.status' => 200]);
        if ($request->photographer_gather_id !== null && $request->photographer_gather_id > 0 && $photographer_work_ids) {
            $photographer_works = $photographer_works->orderByRaw(
                'FIND_IN_SET (`photographer_works`.`id`,\''.implode(',', $photographer_work_ids).'\')'
            );
        }
        if ($request->is_roof_order_by) {
            $photographer_works = $photographer_works->orderBy(
                'photographer_works.roof',
                'desc'
            );
        }
        if ($raw){
            $photographer_works = $photographer_works->whereRaw(
                ' 1=1 OR `id` in (' . implode(',', $photographer_works2->pluck('id')->toArray()) . ')'
            );
        }

        if ($request->only_id){

            $photographer_works = $photographer_works->pluck('id');
        }else{
//            \DB::enableQueryLog();
            $photographer_works = $photographer_works->orderBy(
                'photographer_works.created_at',
                'desc'
            )->orderBy(
                'photographer_works.id',
                'desc'
            )->paginate(
                $request->pageSize
            );
//            dd(\DB::getQueryLog());
            $all_tags = [];
            foreach ($photographer_works as $k => $photographer_work) {
                $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                    PhotographerWorkTag::allowFields()
                )->get()->toArray();
                $all_tags[] = $photographer_work_tags;
            }
            $photographer_works = SystemServer::parsePaginate($photographer_works->toArray());
            $photographer_works['data'] = ArrServer::inData(
                $photographer_works['data'],
                PhotographerWork::allowFields()
            );
            foreach ($photographer_works['data'] as $k => $v) {
                $photographer_works['data'][$k]['review'] = PhotographerWork::getPhotographerWorkReviewStatus($photographer_works['data'][$k]['id']);
                $photographer_works['data'][$k]['gather_ids'] = PhotographerWork::getWorkGatherInfo($photographer_works['data'][$k]['id']);
                $photographer_works['data'][$k]['tags'] = $all_tags[$k];
            }
            $photographer_works['data'] = ArrServer::toNullStrData(
                $photographer_works['data'],
                ['sheets_number', 'shooting_duration']
            );
            $photographer_works['data'] = SystemServer::parsePhotographerWorkCover($photographer_works['data']);
            $photographer_works['data'] = SystemServer::parsePhotographerWorkCustomerIndustry(
                $photographer_works['data']
            );
            $photographer_works['data'] = SystemServer::parsePhotographerWorkCategory($photographer_works['data']);

        }


        return $this->response->array($photographer_works);
    }

    public function photographerWorkInfo(){
        $photographer = $this->_photographer(null, $this->guard);
        $photographer_works = $photographer->photographerWorks();
        $customer = $photographer->photographerWorks()->join(
            'photographer_work_customer_industries',
            'photographer_work_customer_industries.id',
            '=',
            'photographer_works.photographer_work_customer_industry_id'
        )->select(
            'photographer_work_customer_industries.name',
            'photographer_work_customer_industries.pid',
            'photographer_work_customer_industries.id'
        )->where(['photographer_works.status' => 200])->groupBy('photographer_work_customer_industry_id')->get()->toArray();

        $category = $photographer->photographerWorks()->join(
            'photographer_work_categories',
            'photographer_work_categories.id',
            '=',
            'photographer_works.photographer_work_category_id'
        )->select(
            'photographer_work_categories.name',
            'photographer_work_categories.pid',
            'photographer_work_categories.id'
        )->where(['photographer_works.status' => 200])->groupBy('photographer_works.photographer_work_category_id')->get()->toArray();

        $other = $photographer->photographerWorks()->select(
            \DB::raw( 'max(`sheets_number`) as max_sheets_number'),
            \DB::raw( 'min(`sheets_number`) as min_sheets_number'),
            \DB::raw( 'max(`shooting_duration`) as max_shooting_duration'),
            \DB::raw( 'min(`shooting_duration`) as min_shooting_duration'),
            \DB::raw( 'max(`project_amount`) as max_project_amount'),
            \DB::raw( 'min(`project_amount`) as min_project_amount')
        )->where(['photographer_works.status' => 200])->get()->toArray();
        $data = [
            'customer_industry' => $customer,
            'category' => $category,
            'other' => $other
        ];
        return $this->response->array($data);
    }

    /**
     * 我的用户项目详情
     * @param UserRequest $request
     */
    public
    function photographerWork(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('用户项目不存在', 500);
        }
        $photographer = $this->_photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
//        if ($photographer_work->photographer_id != $photographer->id) {
//            return $this->response->error('用户项目不存在', 500);
//        }
        $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
            PhotographerWorkSource::allowFields()
        )->where('status', 200)->orderBy('sort', 'asc')->get();
        $photographer_work_sources = SystemServer::getPhotographerWorkSourcesThumb($photographer_work_sources);
        $photographer_work_sources = $photographer_work_sources->toArray();
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
//        $photographer_work = ArrServer::toNullStrData(
//            $photographer_work,
//            ['sheets_number', 'shooting_duration']
//        );
//        if ($photographer_work['sheets_number'] === null){
//            $photographer_work['sheets_number'] = '';
//        }
//        if ($photographer_work['shooting_duration'] === null){
//            $photographer_work['shooting_duration'] = '';
//        }

        $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
        $photographer_work['sources'] = $photographer_work_sources;
        $photographer_work['tags'] = $photographer_work_tags;
        $photographer_work['photographer'] = ArrServer::inData(
            $photographer->toArray(),
            Photographer::allowFields()
        );
        $photographer_work['photographer'] = SystemServer::parseRegionName($photographer_work['photographer']);
        $photographer_work['photographer'] = SystemServer::parsePhotographerRank(
            $photographer_work['photographer']
        );
        $photographer_work['xacode'] = PhotographerWork::getXacode($photographer_work['id'], false);
        $photographer_work['gather_ids'] = PhotographerWork::getWorkGatherInfo($photographer_work['id']);

        return $this->response->array($photographer_work);
    }

    /**
     * 我的用户作品资源列表
     * @param UserRequest $request
     */
    public
    function photographerWorkSources(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
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
        );
        if ($request->is_roof_order_by) {
            $photographerWorkSources = $photographerWorkSources->orderBy(
                'photographer_works.roof',
                'desc'
            );
        }
        $photographerWorkSources = $photographerWorkSources->orderBy(
            'photographer_works.created_at',
            'desc'
        )->orderBy(
            'photographer_works.id',
            'desc'
        )->orderBy(
            'photographer_work_sources.sort',
            'asc'
        )->paginate(
            $request->pageSize
        );
        $photographerWorkSources = SystemServer::getPhotographerWorkSourcesThumb($photographerWorkSources);
        foreach ($photographerWorkSources as $k => $photographerWorkSource) {
            $photographer_work = PhotographerWork::where(
                ['id' => $photographerWorkSource->photographer_work_id]
            )->first();
            if ($photographer_work) {
                $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                    PhotographerWorkTag::allowFields()
                )->get()->toArray();
                $photographer_work = ArrServer::inData(
                    $photographer_work->toArray(),
                    PhotographerWork::allowFields()
                );
                $photographer_work = ArrServer::toNullStrData(
                    $photographer_work,
                    ['sheets_number', 'shooting_duration']
                );
                $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
                $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
                $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
                $photographer_work['tags'] = $photographer_work_tags;
            } else {
                $photographer_work = [];
            }
            $photographerWorkSources[$k]['work'] = $photographer_work;
        }
        $photographerWorkSources = SystemServer::parsePaginate($photographerWorkSources->toArray());

        return $this->response->array($photographerWorkSources);
    }

    /**
     * 我的用户作品资源列表(简易版)
     * @param UserRequest $request
     */
    public
    function photographerWorkSourcesSimple(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $fields = array_map(
            function ($v) {
                return 'photographer_work_sources.'.$v;
            },
            ['id', 'photographer_work_id', 'type', 'url', 'deal_url', 'rich_url', 'deal_width', 'deal_height', 'image_ave']
        );
        if ($request->photographer_gather_id){
            $photographerWorks = PhotographerWork::where(['photographer_gather_works.photographer_gather_id' => $request->photographer_gather_id])->join(
                'photographer_gather_works',
                'photographer_gather_works.photographer_work_id',
                '=',
                'photographer_works.id'
            )->select(
                'photographer_works.id'
            )->get()->toArray();
            $photographerWorksid = [];
            foreach ($photographerWorks as $photographerWork){
                array_push( $photographerWorksid, $photographerWork['id']);
            }
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
            )->whereIn('photographer_works.id', $photographerWorksid);
        }else{
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
            );
        }



        if ($request->is_roof_order_by) {
            $photographerWorkSources = $photographerWorkSources->orderBy(
                'photographer_works.roof',
                'desc'
            );
        }
        $photographerWorkSources = $photographerWorkSources->orderBy(
            'photographer_works.created_at',
            'desc'
        )->orderBy(
            'photographer_works.id',
            'desc'
        )->orderBy(
            'photographer_work_sources.sort',
            'asc'
        )->paginate(
            $request->pageSize
        );
        $photographerWorkSources = SystemServer::getPhotographerWorkSourcesThumb(
            $photographerWorkSources,
            'imageMogr2/auto-orient/thumbnail/600x'
        );
        foreach ($photographerWorkSources as $k => $photographerWorkSource) {
            $photographer_work = PhotographerWork::select(
                ['id', 'photographer_id', 'photographer_id', 'photographer_work_category_id', 'name', 'customer_name']
            )->where(
                ['id' => $photographerWorkSource->photographer_work_id]
            )->first()->toArray();
            if ($photographer_work) {
                $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work, false, true);
                $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
            } else {
                $photographer_work = [];
            }
            $photographerWorkSources[$k]['work'] = $photographer_work;
            unset($photographerWorkSources[$k]->url, $photographerWorkSources[$k]->deal_url);
        }
        $photographerWorkSources = SystemServer::parsePaginate($photographerWorkSources->toArray());

        return $this->response->array($photographerWorkSources);
    }

    /**
     * 我的用户统计信息
     * @param UserRequest $request
     * @return mixed|void
     */
    public
    function photographerStatistics(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $user = auth($this->guard)->user();
        $rankListLast = $request->rankListLast ?? 50;
        $photographer = $this->_photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        // 获取一下上次的登录时间
        $user_growth_count = UserGrowths::getUserGrowthCount($user, $photographer);
        $photographer_work_count = PhotographerWork::where(
            ['photographer_id' => $photographer->id, 'status' => 200]
        )->count();
        $visitor_count = Visitor::where(
            ['photographer_id' => $photographer->id]
        )->count();
        $view_record_count = ViewRecord::where(
            ['photographer_id' => $photographer->id]
        )->count();
        $photographers = PhotographerServer::visitorRankingList(null, '`photographers`.`id`');
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
            if ($k == $rankListLast - 1) {
                $visitor_today_count_rank_list_last = $p->visitor_today_count;
                $visitor_count_rank_list_last = $p->visitor_count;
            }
            $photographer_count++;
        }
        if ($photographer_count > $rankListLast) {
            $visitor_today_count_differ = $visitor_today_count_rank_list_last - $visitor_today_count_my;
            $visitor_count_differ = $visitor_count_rank_list_last - $visitor_count_my;
        }
        $visitor_today_count = $visitor_today_count_my;

        return $this->responseParseArray(
            compact(
                'photographer_work_count',
                'visitor_count',
                'visitor_today_count',
                'view_record_count',
                'myRank',
                'visitor_today_count_differ',
                'visitor_count_differ',
                'user_growth_count'
            )
        );
    }

    /**
     * 删除我的用户项目
     * @param UserRequest $request
     */
    public
    function photographerWorkDelete(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('用户项目不存在', 500);
        }
        $photographer = $this->_photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        if ($photographer_work->photographer_id != $photographer->id) {
            return $this->response->error('用户项目不存在', 500);
        }
        \DB::beginTransaction();//开启事务
        try {
            $photographer_work->status = 400;
            $photographer_work->save();
            PhotographerGatherWork::where(['photographer_work_id' => $photographer_work->id])->delete();//删除合集中关联的项目
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 项目相关参数显影
     * @param UserRequest $request
     */
    public
    function photographerWorkHide(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
        $photographer_work = PhotographerWork::where(
            [
                'status' => 200,
                'id' => $request->photographer_work_id,
                'photographer_id' => $photographer->id,
            ]
        )->first();
        if (empty($photographer_work)) {
            return $this->response->error('用户项目不存在', 500);
        }
        $type = $request->input('type');
        $status = $request->input('status');

        $types = ['hide_project_amount', 'hide_sheets_number', 'hide_shooting_duration'];

        $updateResult = PhotographerWork::where(
            [
                'id' => $request->photographer_work_id,
                'photographer_id' => $photographer->id,
            ]
        )->update(
            [$types[$type] => $status]
        );


        if (!$updateResult) {
            $this->response->array(
                [
                    'message' => '点击过于频繁',
                    'status' => 200,
                ]
            );
        }


        return $this->response->array(
            [
                'message' => '更改成功',
                'status' => 200,
            ]
        );
    }

    /**
     * 保存我的用户信息
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public
    function savePhotographerInfo(
        PhotographerRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            //验证短信验证码 手机号码不验证
//            $verify_result = SystemServer::verifySmsCode(
//                $request->mobile,
//                $request->sms_code,
//                'update_my_photographer_info',
//                $request->getClientIp()
//            );
//            if ($verify_result['status'] != 'SUCCESS') {
//                \DB::rollback();//回滚事务
//
//                return $this->response->error($verify_result['message'], 500);
//            }
            $photographer = $this->_photographer(null, $this->guard);
//            //验证手机号的唯一性
//            $other_photographer = Photographer::where('id', '!=', $photographer->id)->where(
//                ['mobile' => $request->mobile, 'status' => 200]
//            )->first();
//            if ($other_photographer) {
//                \DB::rollback();//回滚事务
//
//                return $this->response->error('该手机号已经创建过云作品', 500);
//            }
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('用户不存在', 500);
            }
            if ($request->avatar !== null) {
                $photographer->avatar = $request->avatar;
            }
            $photographer->name = $request->name;
            $photographer->gender = $request->gender ?? 0;
            $photographer->province = $request->province;
            $photographer->city = $request->city;
            $photographer->area = $request->area;
            $photographer->photographer_rank_id = $request->photographer_rank_id;
            $photographer->wechat = $request->wechat;
//            $photographer->mobile = $request->mobile;
            $photographer->mobilecontact = $request->mobilecontact;
            $photographer->email = $request->email;
            $photographer->save();
            PhotographerInfoTag::where(['photographer_id' => $photographer->id])->whereIn(
                'type',
                ['auth', 'award', 'educate', 'equipment', 'social']
            )->delete();
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
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存我的用户头像
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public
    function savePhotographerAvatar(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();
        try {
            $photographer = $this->_photographer(null, $this->guard);
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('用户不存在', 500);
            }
            //检查头像
            CheckImgSecurity::dispatch($photographer, $request->avatar)->onConnection('redis')->onQueue('default2');

            $photographer->review = 0;
            $photographer->save();
            \DB::commit();

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存我的用户背景图片
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public
    function savePhotographerBgImg(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = $this->_photographer(null, $this->guard);
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('用户不存在', 500);
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
    public
    function setRoof(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = $this->_photographer(null, $this->guard);
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('用户不存在', 500);
            }
            $photographerWork = $photographer->photographerWorks()->where(
                ['id' => $request->photographer_work_id, 'status' => 200]
            )->first();
            if (!$photographerWork) {
                return $this->response->error('用户项目不存在', 500);
            }
            $photographerWorks = $photographer->photographerWorks()->select(['id', 'roof'])->where(
                ['status' => 200]
            )->where(
                'roof',
                '>',
                0
            )->where('id', '!=', $request->photographer_work_id)->orderBy('roof', 'asc')->get();
            if ($request->operate_type == 1) {
                if ($photographerWork->roof == 0) {
                    if (count($photographerWorks) >= 3) {
                        return $this->response->error('数量有限！最多只能置顶3组作品哦', 500);
                    }
                }
                $roof = 1;
                foreach ($photographerWorks as $k => $tmp_photographerWork) {
                    $tmp_photographerWork->roof = $k + 1;
                    $tmp_photographerWork->save();
                    $roof++;
                }
                $photographerWork->roof = $roof;
            } else {
                foreach ($photographerWorks as $k => $tmp_photographerWork) {
                    $tmp_photographerWork->roof = $k + 1;
                    $tmp_photographerWork->save();
                }
                $photographerWork->roof = 0;
            }
            $photographerWork->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 修改我的用户项目
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public
    function savePhotographerWorkInfo(
        UserRequest $request
    ) {
        $sources_count = 0;
        if ($request->sources) {
            $sources_count = count($request->sources);
        }
        $fsids_count = 0;
        if ($request->fsids) {
            $fsids_count = count($request->fsids);
        }
        $count = $sources_count + $fsids_count;
//        if ($count < 1) {
//            return $this->response->error('资源和网盘文件总和至少为1个', 500);
//        }
//        if ($count > 18) {
//            return $this->response->error('资源和网盘文件总和至多为18个', 500);
//        }
        $this->notPhotographerIdentityVerify();
        $asynchronous_task = [];
        \DB::beginTransaction();//开启事务
        try {
            $user_id = auth($this->guard)->id();
            $photographer = $this->_photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(
                ['id' => $request->photographer_work_id, 'status' => 200]
            )->first();
            if (!$photographer_work) {
                return $this->response->error('用户项目不存在', 500);
            }
            $old_work_params = [
                'name' => $photographer_work->name,
                'source_count' => $photographer_work->photographerWorkSources()->where(['status' => 200])->count(),
            ];
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
            //批量添加项目到合集中
            if ($request->photographer_gather_id){
                foreach($request->photographer_gather_id as $photographer_gather_id){
                    $photographergather = PhotographerGather::where(['id' => $photographer_gather_id])->first();
                    if ($photographergather){
                        $pgw = PhotographerGatherWork::where(['photographer_work_id' => $photographer_work->id, 'photographer_gather_id' => $photographer_gather_id])->first();
                        if (!$pgw){
                            $lastpgw = PhotographerGatherWork::where(['photographer_gather_id' => $photographer_gather_id])->select(
                                \DB::raw("MAX(sort) as maxsort")
                            )->first();
                            $pgw = new PhotographerGatherWork();
                            $pgw->photographer_gather_id = $photographergather->id;
                            $pgw->photographer_work_id = $photographer_work->id;
                            $pgw->sort = $lastpgw->maxsort;
                            if ($lastpgw){
                                $pgw->sort = $lastpgw->maxsort + 1;
                            }
                            $pgw->save();
                        }
                    }
                }
                PhotographerGatherWork::where(['photographer_work_id' => $photographer_work->id])->whereRaw('photographer_gather_id not in ('. implode(',', $request->photographer_gather_id ) .')')->delete();
            }else{
                PhotographerGatherWork::where(['photographer_work_id' => $photographer_work->id])->delete();
            }
            //智能合集
            PhotographerGather::autoGatherWork($photographer->id, $photographer_work);

            if ($request->tags) {
                PhotographerWorkTag::where(['photographer_work_id' => $photographer_work->id])->delete();
                try{
                    $tags = json_decode($request->tags , true);
                    foreach ($request->tags as $v) {
                        $photographer_work_tag = PhotographerWorkTag::create();
                        $photographer_work_tag->photographer_work_id = $photographer_work->id;
                        $photographer_work_tag->name = $v;
                        $photographer_work_tag->save();
                    }
                }catch (\Exception $e){

                }
            }
            $photographer_work->photographerWorkSources()->where(['status' => 200])->update(['status' => 300]);
            if ($request->sources) {
                foreach ($request->sources as $k => $v) {
                    $photographer_work_source = PhotographerWorkSource::where(
                        ['photographer_work_id' => $photographer_work->id, 'status' => 300, 'key' => $v['key']]
                    )->first();
                    if ($photographer_work_source) {
                        $photographer_work_source->sort = $v['sort'];
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
                        $photographer_work_source->sort = $v['sort'];
                        $photographer_work_source->status = 200;
                        $photographer_work_source->save();
                        if ($photographer_work_source->type == 'image') {
                            $photographer_work_source->is_new_source = 1;
                            $photographer_work_source->size = $v['fsize'];
                            $photographer_work_source->width = $v['width'];
                            $photographer_work_source->height =  $v['height'];
                            $photographer_work_source->image_ave = $v['imageAve'];
                            $photographer_work_source->exif = json_encode($v['exif']);
                            $photographer_work_source->save();

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

                        } elseif ($photographer_work_source->type == 'video') {
                            $photographer_work_source->size = $v['fsize'];
                            $photographer_work_source->deal_size = $v['fsize'];;
                            $photographer_work_source->rich_size = $v['fsize'];;
                            $photographer_work_source->save();
                        }
                    }
                }
            }
            if ($request->fsids) {
                $fsids = [];
                $sorts = [];
                foreach ($request->fsids as $fsid) {
                    $fsids[] = $fsid['fsid'];
                    $sorts[$fsid['fsid']] = $fsid['sort'];
                }
                $access_token = $this->_getBaiduAccessToken();
                $url = "https://pan.baidu.com/rest/2.0/xpan/multimedia?method=filemetas";
                $data = [];
                $data['access_token'] = $access_token;
                $data['fsids'] = '['.implode(',', $fsids).']';
                $data['dlink'] = 1;
                $response = $this->_request(
                    'GET',
                    $url,
                    $data,
                    true
                );
                if ($response['errno'] !== 0) {
                    \DB::rollback();//回滚事务

                    return $this->response->array($response);
                }
                if (count($response['list']) > 0) {
                    foreach ($response['list'] as $file) {
                        if ($file['category'] != 3 && $file['category'] != 1) {
                            \DB::rollback();//回滚事务

                            return $this->response->error('必须选择图片或视频', 500);
                        }
                    }
                    foreach ($response['list'] as $k => $file) {
                        $photographer_work_source = PhotographerWorkSource::create();
                        $photographer_work_source->photographer_work_id = $photographer_work->id;
                        if ($file['category'] == 1) {
                            $photographer_work_source->type = 'video';
                        } elseif ($file['category'] == 3) {
                            $photographer_work_source->type = 'image';
                        }
                        $photographer_work_source->origin = 'baidu_disk';
                        $photographer_work_source->sort = $sorts[$file['fs_id']] ?? 0;
                        $photographer_work_source->status = 200;
                        $photographer_work_source->save();
                        $asyncBaiduWorkSourceUpload = AsyncBaiduWorkSourceUpload::create();
                        $asyncBaiduWorkSourceUpload->photographer_work_source_id = $photographer_work_source->id;
                        $asyncBaiduWorkSourceUpload->fs_id = $file['fs_id'];
                        $asyncBaiduWorkSourceUpload->category = $file['category'];
                        $asyncBaiduWorkSourceUpload->size = $file['size'];
                        $asyncBaiduWorkSourceUpload->save();
                        if ($file['category'] == 1) {
                            $type = 'video';
                        } elseif ($file['category'] == 3) {
                            $type = 'image';
                        } else {
                            $type = 'file';
                        }
                        $asynchronous_task[] = [
                            'task_type' => 'qiniuFetchBaiduPan',
                            'asyncBaiduWorkSourceUpload_id' => $asyncBaiduWorkSourceUpload->id,
                            'type' => $type,
                            'url' => $file['dlink'].'&access_token='.$access_token,
                            'callbackurl' => config(
                                    'app.url'
                                ).'/api/notify/qiniu/fetch?async_baidu_work_source_upload_id='.$asyncBaiduWorkSourceUpload->id,
                            'asyncBaiduWorkSourceUpload' => $asyncBaiduWorkSourceUpload,
                        ];
                    }
                }
            }
            $photographer_work->photographerWorkSources()->where(['status' => 300])->update(['status' => 400]);
            $new_work_params = [
                'name' => $photographer_work->name,
                'source_count' => $photographer_work->photographerWorkSources()->where(['status' => 200])->count(),
            ];
            $photographerWorkSources = PhotographerWorkSource::where(['photographer_work_id' => $photographer_work->id, 'status' => 200])->orderBy(
                'sort',
                'asc'
            )->get();
            $editIsRunGenerateWatermark = PhotographerWorkSource::editIsRunGenerateWatermark(
                $new_work_params,
                $old_work_params
            );
            foreach ($photographerWorkSources as $photographerWorkSource) {
                /*exif END*/
                $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
                $bucket = 'zuopin';
                $asynchronous_task[] = [
                    'task_type' => 'qiniuPfop',
                    'bucket' => $bucket,
                    'key' => $photographerWorkSource->key,
                    'fops' => $fops,
                    'pipeline' => null,
                    'notifyUrl' => config(
                            'app.url'
                        ).'/api/notify/qiniu/fopDeal?photographer_work_source_id='.$photographerWorkSource->id,
                    'useHTTPS' => true,
                    'error_step' => '处理图片持久请求',
                    'error_msg' => '七牛持久化接口返回错误信息',
                    'error_request_data' => $request->all(),
                    'error_photographerWorkSource' => $photographerWorkSource,
                ];
                if ($photographerWorkSource->is_new_source) {
                    $photographerWorkSource->is_new_source = 0;
                    $photographerWorkSource->save();
                }
                $qiniuPfopRichSourceJob = QiniuPfopRichSourceJob::create(
                    [
                        'photographer_work_source_id' => $photographerWorkSource->id,
                        'edit_node' => '修改项目',
                        'edit_at' => date('Y-m-d H:i:s'),
                    ]
                );
            }
            \DB::commit();//提交事务
            AsynchronousTask::dispatch($asynchronous_task)->onConnection('redis')->onQueue('default2');
            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 随机用户列表
     * @return mixed|void
     */
    public
    function randomPhotographers()
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
                    $photographerWorkSources = PhotographerWorkSource::select(
                        $fields
                    )->join(
                        'photographer_works',
                        'photographer_work_sources.photographer_work_id',
                        '=',
                        'photographer_works.id'
                    )->where(
                        [
                            'photographer_works.photographer_id' => $photographer['id'],
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
                        'photographer_works.id',
                        'desc'
                    )->orderBy(
                        'photographer_work_sources.sort',
                        'asc'
                    )->take(3)->get();
                    $photographerWorkSources = SystemServer::getPhotographerWorkSourcesThumb(
                        $photographerWorkSources
                    );

                    $photographers[$k]['photographer_work_sources'] = $photographerWorkSources->toArray();
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
     * 获取我的浏览用户记录
     * @param UserRequest $request
     * @return mixed
     */
    public
    function viewRecords(
        UserRequest $request
    ) {
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
                $work_limit = (int)$request->work_limit;
                if ($work_limit > 0) {
                    $photographerWorks = PhotographerWork::select(PhotographerWork::allowFields())->where(
                        [
                            'photographer_id' => $photographer['id'],
                            'status' => 200,
                        ]
                    )->orderBy('roof', 'desc')->orderBy(
                        'created_at',
                        'desc'
                    )->orderBy('id', 'desc')->take(
                        $work_limit
                    )->get();
                    $all_tags = [];
                    foreach ($photographerWorks as $_k => $photographerWork) {
                        $photographerWorkTags = $photographerWork->photographerWorkTags()->select(
                            PhotographerWorkTag::allowFields()
                        )->get()->toArray();
                        $all_tags[] = $photographerWorkTags;
                    }
                    $photographerWorks = $photographerWorks->toArray();
                    $photographerWorks = ArrServer::toNullStrData(
                        $photographerWorks,
                        ['sheets_number', 'shooting_duration']
                    );
                    $photographerWorks = ArrServer::inData($photographerWorks, PhotographerWork::allowFields());
                    foreach ($photographerWorks as $_k => $v) {
                        $photographerWorks[$_k]['tags'] = $all_tags[$_k];
                    }
                    $photographerWorks = SystemServer::parsePhotographerWorkCover($photographerWorks);
                    $photographerWorks = SystemServer::parsePhotographerWorkCustomerIndustry($photographerWorks);
                    $photographerWorks = SystemServer::parsePhotographerWorkCategory($photographerWorks);
                    $view_records['data'][$k]['works'] = $photographerWorks;
                }
                $source_limit = (int)$request->source_limit;
                if ($source_limit > 0) {
                    $photographerWorkSources = PhotographerWorkSource::select(
                        $fields
                    )->join(
                        'photographer_works',
                        'photographer_work_sources.photographer_work_id',
                        '=',
                        'photographer_works.id'
                    )->where(
                        [
                            'photographer_works.photographer_id' => $photographer['id'],
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
                        'photographer_works.id',
                        'desc'
                    )->orderBy(
                        'photographer_work_sources.sort',
                        'asc'
                    )->take($source_limit)->get();
                    $photographerWorkSources = SystemServer::getPhotographerWorkSourcesThumb(
                        $photographerWorkSources
                    );
                    $view_records['data'][$k]['sources'] = $photographerWorkSources->toArray();
                }
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
    public
    function saveDocPdf(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
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
    public
    function docPdfs(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
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
    public
    function getDocPdfStatus(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
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
    public
    function docPdfDelete(
        UserRequest $request
    ) {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
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

    /**
     * 获取用户的分享图
     * @return mixed|void
     */
    public
    function photographerShare(
        Request $request
    ) {
        $photographer_id = $request->input('photographer_id');
        $photographer_gather_id = $request->input('photographer_gather_id');
        $photographer = Photographer::where('id', $photographer_id)->first();

        if (empty($photographer)) {
            return [
                'result' => false,
                'share_url' => '',
            ];
        }
        $Photographer = new Photographer();

        return [
            'result' => true,
            'share_url' => $Photographer->generateShare($photographer_id, $photographer_gather_id),
        ];
    }

    /**
     * 获取项目的分享图
     * @return mixed|void
     */
    public
    function photographerWorkShare(
        Request $request
    ) {
        $photographer = $this->_photographer();
        $photographer_work_id = $request->input('photographer_work_id', 0);
        $PhotographerWork = new PhotographerWork();
        $buckets = config('custom.qiniu.buckets');
        if (empty($PhotographerWork)) {
            return [
                'result' => false,
                'share_url' => '',
            ];
        } else {
            return [
                'result' => true,
                'share_url' => $PhotographerWork->generateShare($photographer_work_id, $photographer),
            ];
        }
    }

    public function generateWatermarkErrorFeedback(Request $request){
        $photographer = $this->_photographer();
        $important = 0;
        $user = User::where(['photographer_id' => $photographer->id])->first();
        $data['attachment'] = json_encode([]);
        $data['page'] = '其他';
        $data['type'] = 1;
        $data['content'] = '用户水印图片未生成成功';
        $data['important'] = $important;
        $data['created_at'] = date('Y-m-d H:i:s', time());
        $data['updated_at'] = date('Y-m-d H:i:s', time());
        $data['mobile_version'] = $request->input('mobile_version', '');
        $data['system_version'] = $request->input('system_version', '');
        $data['wechat_version'] = $request->input('wechat_version', '');
        $data['language'] = 'zh_CN';
        $data['user_id'] = $user->id;
        Question::insert($data);

        return $this->response->noContent();
    }

    public function filterrecord(UserRequest $request){
//        $record = new PhotographerGatherFilterRecord();
//        $record-
    }

}
