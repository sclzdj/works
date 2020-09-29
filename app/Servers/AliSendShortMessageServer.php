<?php

namespace App\Servers;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use App\Model\Index\SendAliShortMessageLog;

/**
 * 阿里发送短信
 * Class AliSendShortMessageServer
 * @package App\Servers
 */
class AliSendShortMessageServer
{
    /*公共参数*/
    private $AccessKeyId = null;//主账号AccessKey的ID。
    private $AccessSecret = null;//主账号AccessSecret。
    private $RegionId = null;//服务节点。
    private $Scheme = null;//https|http
    private $Product = 'Dysmsapi';//产品名称
    private $Host = 'dysmsapi.aliyuncs.com';//产品域名,开发者无需替换
    private $Method = 'POST';//发送方式
    private $Version = '2017-05-25';//版本
    private $Action = null;//系统规定参数。取值：SendSms,SendBatchSms,QuerySendDetails。
    private $TemplateCode = null;//SMS_152550005 短信模板ID。(SendSms,SendBatchSms这两个方法使用)请在控制台模板管理页面模板CODE一列查看。说明 必须是已添加、并通过审核的短信签名；且发送国际/港澳台消息时，请使用国际/港澳台短信模版。

    /*SendSms*/
    public $SignName = null;//阿里云 短信签名名称。请在控制台签名管理页面签名名称一列查看。说明 必须是已添加、并通过审核的短信签名。
    public $PhoneNumbers = null;//15900000000,18353621777 接收短信的手机号码。格式：国内短信：11位手机号码，例如15951955195。国际/港澳台消息：国际区号+号码，例如85200000000。支持对多个手机号码发送短信，手机号码之间以英文逗号（,）分隔。上限为1000个手机号码。批量调用相对于单条调用及时性稍有延迟。说明 验证码类型短信，建议使用单独发送的方式。
    public $OutId = null;//abcdefgh 外部流水扩展字段。
    public $SmsUpExtendCode = null;//90999 上行短信扩展码，无特殊需要此字段的用户请忽略此字段。
    public $TemplateParam = null;//{"code":"1111"} 短信模板变量对应的实际值，JSON格式。说明 如果JSON中需要带换行符，请参照标准的JSON协议处理。

    /*SendBatchSms*/
    public $PhoneNumberJson = null;//["15900000000","13500000000"] 接收短信的手机号码，JSON数组格式。手机号码格式：国内短信：11位手机号码，例如15900000000。国际/港澳台消息：国际区号+号码，例如85200000000。说明 验证码类型短信，建议使用接口SendSms单独发送。
    public $SignNameJson = null;//["阿里云","阿里巴巴"] 短信签名名称，JSON数组格式。请在控制台签名管理页面签名名称一列查看。说明 必须是已添加、并通过审核的短信签名；且短信签名的个数必须与手机号码的个数相同、内容一一对应。
    public $SmsUpExtendCodeJson = null;//["90999","90998"] 上行短信扩展码，JSON数组格式。无特殊需要此字段的用户请忽略此字段。
    public $TemplateParamJson = null;//[{"name":"TemplateParamJson"},{"name":"TemplateParamJson"}] 短信模板变量对应的实际值，JSON格式。说明 如果JSON中需要带换行符，请参照标准的JSON协议处理；且模板变量值的个数必须与手机号码、签名的个数相同、内容一一对应，表示向指定手机号码中发对应签名的短信，且短信模板中的变量参数替换为对应的值。

    /*QuerySendDetails*/
    public $CurrentPage = null;//1 分页查看发送记录，指定发送记录的的当前页码。
    public $PageSize = null;//10 分页查看发送记录，指定每页显示的短信记录数量。取值范围为1~50。
    public $PhoneNumber = null;//15900000000 接收短信的手机号码。格式：国内短信：11位手机号码，例如15900000000。国际/港澳台消息：国际区号+号码，例如85200000000。
    public $SendDate = null;//20181225 短信发送日期，支持查询最近30天的记录。格式为yyyyMMdd，例如20181225。
    public $BizId = null;//134523^4351232 发送回执ID，即发送流水号。调用发送接口SendSms或SendBatchSms发送短信时，返回值中的BizId字段。

    public function __construct(
        $TemplateCode = null,
        $AccessKeyId = null,
        $AccessSecret = null,
        $RegionId = null,
        $Scheme = null
    ) {
        if (!is_null($TemplateCode)) {
            $this->TemplateCode = $TemplateCode;
        }
        if (is_null($AccessKeyId)) {
            $this->AccessKeyId = config('custom.send_short_message.ali.AccessKeyId');
        } else {
            $this->AccessKeyId = $AccessKeyId;
        }
        if (is_null($AccessSecret)) {
            $this->AccessSecret = config('custom.send_short_message.ali.AccessSecret');
        } else {
            $this->AccessSecret = $AccessSecret;
        }
        if (is_null($RegionId)) {
            $this->RegionId = config('custom.send_short_message.ali.RegionId');
        } else {
            $this->RegionId = $RegionId;
        }
        if (is_null($Scheme)) {
            $this->Scheme = config('custom.send_short_message.ali.Scheme');
        } else {
            $this->Scheme = $Scheme;
        }
    }

    /**
     * 记录发送日志
     * @param $mobile
     * @param $template_code
     * @param $content_vars
     * @param $status
     * @param $third_response
     * @return mixed
     */
    public static function sendLog($mobile, $template_code, $content_vars, $status, $third_response)
    {
        if (is_array($content_vars)) {
            $content_vars = json_encode($content_vars, JSON_UNESCAPED_UNICODE);
        }
        if (is_array($third_response)) {
            $third_response = json_encode($third_response, JSON_UNESCAPED_UNICODE);
        }
        $sendAliShortMessageLog = SendAliShortMessageLog::create();
        $sendAliShortMessageLog->mobile = $mobile;
        $sendAliShortMessageLog->template_code = $template_code;
        $sendAliShortMessageLog->content_vars = $content_vars;
        $sendAliShortMessageLog->status = $status;
        $sendAliShortMessageLog->third_response = $third_response;
        $sendAliShortMessageLog->save();

        return $sendAliShortMessageLog->id;
    }

    /**
     * 快捷调用SendSms发送短信
     * @param $purpose
     * @param $TemplateCodes
     * @param $mobile
     * @param $content_vars
     * @param $return_type
     * @return mixed 返回发送短信记录id
     * @throws ClientException
     */
        public static function quickSendSms($mobile, $TemplateCodes, $purpose, $content_vars = [], $return_type = 0)
    {
        $AliSendShortMessageServer = new AliSendShortMessageServer(
            $TemplateCodes[$purpose]['TemplateCode']
        );
        $AliSendShortMessageServer->SignName = $TemplateCodes[$purpose]['SignName'];
        $AliSendShortMessageServer->PhoneNumbers = $mobile;
        $AliSendShortMessageServer->TemplateParam = $content_vars;
        $ali_result = $AliSendShortMessageServer->sendSms();
        $ali_status = ($ali_result['status'] == 'SUCCESS' && $ali_result['data']['Code'] == 'OK') ? 200 : 500;
        $sendAliShortMessageLog_id = self::sendLog(
            $mobile,
            $TemplateCodes[$purpose]['TemplateCode'],
            $content_vars,
            $ali_status,
            $ali_result
        );
        if ($return_type == 0) {
            return $sendAliShortMessageLog_id;
        } elseif ($return_type == 1) {
            return $ali_result;
        } else {
            return compact('sendAliShortMessageLog_id', 'ali_result', 'ali_status');
        }
    }

    /**
     * 调用SendSms发送短信。
     * SendSms接口是短信发送接口，支持在一次请求中向多个不同的手机号码发送同样内容的短信。
     * 如果您需要在一次请求中分别向多个不同的手机号码发送不同签名和模版内容的短信，请使用SendBatchSms接口。
     * 调用该接口发送短信时，请注意：
     * 发送短信会根据发送量计费，价格请参考计费说明。
     * 在一次请求中，最多可以向1000个手机号码发送同样内容的短信。
     * @return array
     * @throws ClientException
     */
    public function sendSms()
    {
        $this->Action = 'SendSms';
        AlibabaCloud::accessKeyClient($this->AccessKeyId, $this->AccessSecret)->regionId(
            $this->RegionId
        )->asDefaultClient();
        $query = [];
        if (!is_null($this->RegionId)) {
            $query['RegionId'] = $this->RegionId;
        }
        if (!is_null($this->PhoneNumbers)) {
            if (!is_array($this->PhoneNumbers)) {
                $this->PhoneNumbers = explode(',', $this->PhoneNumbers);
            }
            if (count($this->PhoneNumbers) > 1000) {
                return ['status' => 'ERROR', 'data' => '最多只能对1000个手机号发送，请分开发送'];
            }
            $this->PhoneNumbers = implode(',', $this->PhoneNumbers);
            $query['PhoneNumbers'] = $this->PhoneNumbers;
        }
        if (!is_null($this->SignName)) {
            $query['SignName'] = $this->SignName;
        }
        if (!is_null($this->TemplateCode)) {
            $query['TemplateCode'] = $this->TemplateCode;
        }
        if (!is_null($this->OutId)) {
            $query['OutId'] = $this->OutId;
        }
        if (!is_null($this->SmsUpExtendCode)) {
            $query['SmsUpExtendCode'] = $this->SmsUpExtendCode;
        }
        if ($this->TemplateParam) {
            $query['TemplateParam'] = json_encode($this->TemplateParam);
        }
        try {
            $result = AlibabaCloud::rpc()
                ->product($this->Product)
                ->scheme($this->Scheme)
                ->version($this->Version)
                ->action($this->Action)
                ->method($this->Method)
                ->host($this->Host)
                ->options(['query' => $query])
                ->request();

            return ['status' => 'SUCCESS', 'message' => 'OK', 'data' => $result->toArray()];
        } catch (ClientException $e) {
            return ['status' => 'ERROR', 'message' => $e->getErrorMessage()];
        } catch (ServerException $e) {
            return ['status' => 'ERROR', 'message' => $e->getErrorMessage()];
        }
    }
}
