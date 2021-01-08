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
use App\Model\Index\DeliverWork;
use App\Model\Index\DeliverWorkFile;
use App\Model\Index\DeliverWorkObtain;
use App\Model\Index\DeliverWorkSyncPanJob;
use App\Model\Index\HelpNote;
use App\Model\Index\HelpTagNotes;
use App\Model\Index\HelpTags;
use App\Model\Index\OperateRecord;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerGather;
use App\Model\Index\PhotographerGatherInfo;
use App\Model\Index\PhotographerGatherWork;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkCategory;
use App\Model\Index\PhotographerWorkCustomerIndustry;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\QuesionUser;
use App\Model\Index\RecodeScence;
use App\Model\Index\Settings;
use App\Model\Index\SmsCode;
use App\Model\Index\TargetUser;
use App\Model\Index\User;
use App\Model\Index\ViewRecord;
use App\Model\Index\Visitor;
use App\Model\Index\VisitorTag;
use App\Servers\AliSendShortMessageServer;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use App\Servers\WechatServer;
use function Qiniu\base64_urlSafeDecode;
use function Qiniu\base64_urlSafeEncode;

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
            if(!$user){
                return $this->response->error('微信用户不存在', 500);
            }
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
        // 如果不传 拿到全部这个标签的
        $tag_id = $request->input('tag_id' , 0);
        $page = $request->input('page' , 1);
        if (empty($request->limit)) {
            $request->limit = 15;
        }
        $page = ($page - 1) * $request->limit;
        if ($tag_id == 0) {
            $tag_id = (HelpTags::where('name' , '全部')->first())->id;
        }

        $HelpNote = HelpTagNotes::where('help_tag_notes.tags_id' , $tag_id)
            ->join('help_notes' , 'help_notes.id' , '=' , 'help_tag_notes.help_id')
            ->where('help_notes.status' , 200)
            ->select([
                'help_notes.id',
                'help_notes.title',
                'help_notes.content',
                'help_notes.created_at',
            ]);


       // $HelpNote = HelpNote::select(HelpNote::allowFields())->where('status', 200);
        if (!empty($request->keywords)) {
            $HelpNote = $HelpNote->where('help_notes.title', 'like', '%'.$request->keywords.'%');
        }

        $count = $HelpNote->count();

        $data = $HelpNote->orderBy('help_notes.sort', 'asc')
            ->skip($page)
            ->take($request->limit)
            ->get();



        return $this->response->array(compact('data' , 'count'));

    }

    /**
     * 获取帮助标签
     * @param  $request
     * @return mixed
     */
    public function getHelpTags()
    {
//        $helpnotes = HelpNote::where('status', 200)->get();
//        foreach ($helpnotes as $helpnote) {
//            (new HelpTagNotes())->insert([
//                'tags_id' => 5,
//                'help_id' => $helpnote->id,
//                'created_at' => date('Y-m-d H:i:s')
//            ]);
//        }
        $help_tags = HelpTags::select(['name' ,'id'])->get();

        return $this->responseParseArray($help_tags);
    }

    /**
     * 获取所有地区
     * @return mixed
     */
    public function getRegion()
    {
        $provinces=SystemArea::select(['id','pid','name','short_name'])->where(['pid'=>0,'level'=>1])->orderBy('sort','asc')->get();
        foreach ($provinces as $key=>$province){
            $citys=SystemArea::select(['id','pid','name','short_name'])->where(['pid'=>$province->id,'level'=>2])->orderBy('sort','asc')->get();
            $provinces[$key]['citys']=$citys;
            foreach ($citys as $k=>$city){
                $areas=SystemArea::select(['id','pid','name','short_name'])->where(['pid'=>$city->id,'level'=>3])->orderBy('sort','asc')->get();
                $provinces[$key]['citys'][$k]['areas']=$areas;
            }
        }

        return $this->responseParseArray($provinces);
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
//        dd(base64_urlSafeEncode('https://d.pcs.baidu.com/file/97b1a3ec7fa30ea0c6ed0846b307bd0d?fid=3358187462-250528-292893351675862&rt=pr&sign=FDtAERV-DCb740ccc5511e5e8fedcff06b081203-Pi7HapdSswQmnjEoRMONIZP1bSY%3D&expires=8h&chkbd=0&chkv=2&dp-logid=188724189701184574&dp-callid=0&dstime=1584758919&r=468150683&access_token=123.31f684c2b9470dbfdb735c2ed780b43e.YlnyJRCrqDhR_rkOw_W3jPAbPHusUAOSdBRgOjx.XJM33Q'));
        $dlink = base64_urlSafeDecode($request->dlink);

//        SystemServer::filePutContents('logs/124141.log',json_encode(['dlink'=>$dlink]));
//        $filename = 'logs/'.mt_rand(10000, 99999999);
//        SystemServer::getCurl(
//            $dlink,
//            true,
//            ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36'],
//            $filename
//        );

//        return redirect(config('app.url').'/'.$filename);

        return redirect($dlink);
    }
    /**
     *  检测是否为非法字符
     * @param SystemRequest $request
     */

    public function checkWordSecurity(SystemRequest $request){
        $word = $request->word;
        $flag = WechatServer::checkContentSecurity($word);
        if (!$flag){
            return $this->response->error("含有非法字符!", 500);
        }

        return $this->response->noContent();
    }

    /**
     * 删除用户
     */
    public function deleteUser(SystemRequest $request){
        $userid = $request->userid;
        \DB::beginTransaction();
        try {
            $user = User::where(['id' => $userid])->first();

            $worksobj = PhotographerWork::where(['photographer_id' => $user['photographer_id']]);
            $works = $worksobj->get();
            //删除项目
            if ($works){
                $works = $works->pluck(['id']);
                $workids = $works->toArray();
                $whereraw = ' `id` in (' . implode(',' , $workids) . ')';
                PhotographerWorkSource::whereRaw($whereraw)->delete();
            }
            //删除合集
            $pgsobj = PhotographerGather::where(['photographer_id' => $user['photographer_id']]);
            $pgs = $pgsobj->get();
            if (!$pgs->isEmpty()){
                $pgs = $pgs->pluck(['id']);
                $pgsids = $pgs->toArray();
                $whereraw = ' `photographer_gather_id` in (' . implode(',' , $pgsids) . ')';
                //删除合集归属
                PhotographerGatherWork::whereRaw($whereraw)->delete();

                PhotographerGatherInfo::where(['photographer_id' => $user['photographer_id']])->delete();
            }

            //deliver
            $dwobj = DeliverWork::where(['user_id' => $user['id']]);
            $dw = $dwobj->get();
            if (!$dw->isEmpty()){
                $dw = $dw->pluck(['id']);
                $dwids = $dw->toArray();
                $whereraw = ' `work_id` in (' . implode(',' , $dwids) . ')';
                DeliverWorkFile::whereRaw($whereraw)->delete();

                DeliverWorkObtain::whereRaw($whereraw)->delete();

                DeliverWorkSyncPanJob::where($whereraw)->delete();
            }


            $dwobj->delete();


            $Orwhere = [];
            $Orwhere[] = ['user_id','=', $user['id'], 'OR'];
            $Orwhere[] = ['photographer_id' ,'=',  $user['photographer_id'], 'OR'];

            OperateRecord::where($Orwhere)->delete();

            RecodeScence::where(['user_id' => $user['id']])->delete();

            Visitor::where($Orwhere)->delete();



            ViewRecord::where($Orwhere)->delete();

            TargetUser::where(['user_id' => $user['id']])->delete();

            //删除百度授权
            BaiduOauth::where(['user_id' => $user['id']])->delete();

            //删除问题反馈
            QuesionUser::where(['user_id' => $user['id']])->delete();

            $pgsobj->delete();
            $worksobj->delete();
            Photographer::where(['id' => $user['photographer_id']])->delete();
            User::where(['id' => $userid])->delete();
//
        }catch (\Exception $e){
            \DB::rollBack();

            return $this->response->error("删除失败!", 500);
        }

        \DB::commit();


        return $this->response->noContent();

    }

    public function settings(){
        $settings = Settings::find(1);
        $activity1 = json_decode($settings, true);
        $data = [
            'activity1_time' => json_decode($activity1['activity1'], true)
        ];
        return $this->responseParseArray($data);
    }
}
