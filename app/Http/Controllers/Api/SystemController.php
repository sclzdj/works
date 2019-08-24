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
use App\Model\Index\SmsCode;
use App\Servers\AliSendShortMessageServer;

/**
 * 系统通用
 * Class SystemController
 * @package App\Http\Controllers\Api
 */
class SystemController extends BaseController
{
   public function sendSmsCode(SystemRequest $request){
       $expire=config('custom.send_short_message.sms_code.expire');
       $space=config('custom.send_short_message.sms_code.space');
       $third_type=config('custom.send_short_message.third_type');
       $TemplateCodes=config('custom.send_short_message.'.$third_type.'.TemplateCodes');
       if(!isset($TemplateCodes[$request->purpose])){
           return $this->response->error($request->purpose.'用途未配置', 403);
       }
       $code=mt_rand(100000,999999);
       $sms_code=SmsCode::where(['mobile'=>$request->mobile,'purpose'=>$request->purpose,'is_used'=>0,'ip'=>$request->getClientIp()])->where('created_at','>',date('Y-m-d H:i:s',time()-$space))->orderBy('created_at','desc')->first();
       if($sms_code){
           return $this->response->error('请勿频繁发送验证码', 403);
       }
       \DB::beginTransaction();//开启事务
       try {
           //入库
           $sms_code=SmsCode::create();
           $sms_code->third_type=$third_type;
           $sms_code->mobile=$request->mobile;
           $sms_code->code=$code;
           $sms_code->purpose=$request->purpose;
           $sms_code->ip=$request->getClientIp();
           $sms_code->expired_at=date('Y-m-d H:i:s',time()+$expire);
           if($third_type=='ali'){
               //发送短信
               $AliSendShortMessageServer=new AliSendShortMessageServer($TemplateCodes[$request->purpose]['TemplateCode']);
               $AliSendShortMessageServer->SignName=$TemplateCodes[$request->purpose]['SignName'];
               $AliSendShortMessageServer->PhoneNumbers='18353621790';
               $AliSendShortMessageServer->TemplateParam=['code'=>$code];
               $result=$AliSendShortMessageServer->sendSms();
               if($result['status']!='SUCCESS'){
                   \DB::rollback();//回滚事务

                   return $this->response->error($result['message'], 500);
               }else{
                   if($result['data']['Code']!='OK'){
                       \DB::rollback();//回滚事务

                       return $this->response->error($result['data']['Message'], 500);
                   }else{
                       $sms_code->third_response=json_encode($result['data']);
                       $sms_code->save();
                       \DB::commit();//提交事务

                       return $this->response->noContent();
                   }
               }
           }else{
               \DB::rollback();//回滚事务

               return $this->response->error('暂未开通该第三方短信渠道', 500);
           }
       } catch (\Exception $e) {
           \DB::rollback();//回滚事务

           return $this->response->error($e->getMessage(), 500);
       }
   }
}
