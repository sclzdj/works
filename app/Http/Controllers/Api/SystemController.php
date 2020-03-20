<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/21
 * Time: 15:50
 */

namespace App\Http\Controllers\Api;

use App\Http\Requests\Index\SystemRequest;
use App\Http\Requests\Index\UserRequest;
use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
use App\Model\Index\BaiduOauth;
use App\Model\Index\CrowdFunding;
use App\Model\Index\HelpNote;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWorkCategory;
use App\Model\Index\PhotographerWorkCustomerIndustry;
use App\Model\Index\SmsCode;
use App\Model\Index\User;
use App\Model\Index\VisitorTag;
use App\Servers\AliSendShortMessageServer;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use function Qiniu\base64_urlSafeDecode;

/**
 * 系统通用
 * Class SystemController
 * @package App\Http\Controllers\Api
 */
class SystemController extends BaseController
{
    /**
     * 发送手机验证码
     * @param SystemRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function sendSmsCode(SystemRequest $request)
    {
        //发送短信
        $expire = config('custom.send_short_message.sms_code.expire');
        $space = config('custom.send_short_message.sms_code.space');
        $third_type = config('custom.send_short_message.third_type');
        $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
        if (!isset($TemplateCodes[$request->purpose])) {
            return $this->response->error($request->purpose.'用途未配置', 403);
        }
        if ($request->purpose == 'photographer_register') {
            //验证手机号的唯一性
            $photographer = Photographer::where(
                ['mobile' => $request->mobile, 'status' => 200]
            )->first();
            if ($photographer) {
                return $this->response->error('该手机号已经创建过云作品', 500);
            }
        } elseif ($request->purpose == 'update_my_photographer_info') {
            //验证手机号的唯一性
            $user = auth($this->guards['user'])->user();
            $photographer = Photographer::where('id', '!=', $user->photographer_id)->where(
                ['mobile' => $request->mobile, 'status' => 200]
            )->first();
            if ($photographer) {
                return $this->response->error('该手机号已经创建过云作品', 500);
            }
        }
        $code = mt_rand(100000, 999999);
        $sms_code = SmsCode::where(
            [
                'mobile' => $request->mobile,
                'purpose' => $request->purpose,
                'is_used' => 0,
                'ip' => $request->getClientIp(),
            ]
        )->where('created_at', '>', date('Y-m-d H:i:s', time() - $space))->orderBy('created_at', 'desc')->first();
        if ($sms_code) {
            return $this->response->error('请勿频繁发送验证码', 403);
        }
        \DB::beginTransaction();//开启事务
        try {
            //入库
            $sms_code = SmsCode::create();
            $sms_code->third_type = $third_type;
            $sms_code->mobile = $request->mobile;
            $sms_code->code = $code;
            $sms_code->purpose = $request->purpose;
            $sms_code->ip = $request->getClientIp();
            $sms_code->expired_at = date('Y-m-d H:i:s', time() + $expire);
            if ($third_type == 'ali') {
                $result = AliSendShortMessageServer::quickSendSms(
                    $request->mobile,
                    $TemplateCodes,
                    $request->purpose,
                    ['code' => $code],
                    2
                );
                $ali_result = $result['ali_result'];
                $sendAliShortMessageLog_id = $result['sendAliShortMessageLog_id'];
                if ($ali_result['status'] != 'SUCCESS') {
                    \DB::rollback();//回滚事务

                    return $this->response->error($ali_result['message'], 500);
                } else {
                    if ($ali_result['data']['Code'] != 'OK') {
                        \DB::rollback();//回滚事务

                        return $this->response->error($ali_result['data']['Message'], 500);
                    } else {
                        $sms_code->third_log_id = $sendAliShortMessageLog_id;
                        $sms_code->save();
                        \DB::commit();//提交事务

                        return $this->response->noContent();
                    }
                }
            } else {
                \DB::rollback();//回滚事务

                return $this->response->error('暂未开通该第三方短信渠道', 500);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 获取帮助数据
     * @param SystemRequest $request
     * @return mixed
     */
    public function getHelpNotes(SystemRequest $request)
    {
        $HelpNote = HelpNote::select(HelpNote::allowFields())->where('status', 200);
        if (!empty($request->keywords)) {
            $HelpNote = $HelpNote->where('title', 'like', '%'.$request->keywords.'%');
        }

        $help_notes = $HelpNote->orderBy('sort', 'asc')->take($request->limit)->get();

        return $this->responseParseArray($help_notes);
    }

    /**
     * 获取所有省份
     * @return mixed
     */
    public function getProvinces()
    {
        $system_areas = SystemArea::select(SystemArea::allowFields())->where(['pid' => 0, 'level' => 1])->orderBy(
            'sort',
            'asc'
        )->get();

        return $this->responseParseArray($system_areas);
    }

    /**
     * 获取省份的所有城市
     * @param SystemRequest $request
     * @return mixed
     */
    public function getCitys(SystemRequest $request)
    {
        $system_areas = SystemArea::select(SystemArea::allowFields())->where(
            ['pid' => $request->province_id, 'level' => 2]
        )->orderBy(
            'sort',
            'asc'
        )->get();

        return $this->responseParseArray($system_areas);
    }

    /**
     * 获取城市的所有地方
     * @param SystemRequest $request
     * @return mixed
     */
    public function getAreas(SystemRequest $request)
    {
        $system_areas = SystemArea::select(SystemArea::allowFields())->where(
            ['pid' => $request->city_id, 'level' => 3]
        )->orderBy(
            'sort',
            'asc'
        )->get();

        return $this->responseParseArray($system_areas);
    }

    /**
     * 获取访客的所有标签列表
     * @return mixed
     */
    public function visitorTags()
    {
        $tags = VisitorTag::select(VisitorTag::allowFields())->orderBy('sort', 'asc')->get();

        return $this->responseParseArray($tags);
    }

    /**
     * 获取用户项目的所有分类列表
     * @return mixed
     */
    public function photographerWorkCategories()
    {
        $categories = PhotographerWorkCategory::select(PhotographerWorkCategory::allowFields())->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($categories as $k => $v) {
            $categories[$k]['children'] = PhotographerWorkCategory::select(
                PhotographerWorkCategory::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }

        return $this->responseParseArray($categories);
    }

    /**
     * 获取用户项目的所有客户行业列表
     * @return mixed
     */
    public function PhotographerWorkCustomerIndustries()
    {
        $industries = PhotographerWorkCustomerIndustry::select(PhotographerWorkCustomerIndustry::allowFields())->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($industries as $k => $v) {
            $industries[$k]['children'] = PhotographerWorkCustomerIndustry::select(
                PhotographerWorkCustomerIndustry::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }

        return $this->responseParseArray($industries);
    }

    /**
     * 获取用户的所有头衔列表
     * @return mixed
     */
    public function photographerRanks()
    {
        $ranks = PhotographerRank::select(PhotographerRank::allowFields())->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($ranks as $k => $v) {
            $ranks[$k]['children'] = PhotographerRank::select(
                PhotographerRank::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }


        return $this->responseParseArray($ranks);
    }

    /**
     * 保存百度授权状态
     * @return mixed
     */
    public function baiduOauthStore(SystemRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $expires_in = $request->expires_in;
            $expired_at = date('Y-m-d H:i:s', time() + $expires_in);
            $baidu_oauth = BaiduOauth::where(['user_id' => $request->user_id])->first();
            if (!$baidu_oauth) {
                $baidu_oauth = BaiduOauth::create();
                $baidu_oauth->user_id = $request->user_id;
            }
            $baidu_oauth->access_token = $request->access_token;
            $baidu_oauth->expired_at = $expired_at;
            $baidu_oauth->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 获取系统配置
     * @return \Dingo\Api\Http\Response|void
     */
    public function configs()
    {
        $configs = SystemConfig::select(['title', 'name', 'value'])->where(['type' => 'works'])->get();

        return $this->responseParseArray($configs);
    }

    /**
     * 百度网盘dlink转发
     * @param SystemRequest $request
     */
    public function baiduDlink(SystemRequest $request)
    {
        $dlink = base64_urlSafeDecode($request->dlink);

        return redirect($dlink, 302, ['User-Agent' => 'www.zuopin.cloud']);
    }
}
