<?php

namespace App\Servers;

use App\Model\Index\AsyncBaiduWorkSourceUpload;
use App\Model\Index\PhotographerWorkSource;

class ErrLogServer
{
    /**
     * 七牛第三方抓取报错记录
     * @param $msg 报错信息
     * @param $request_data 通知数据或报错数据
     * @param AsyncBaiduWorkSourceUpload|null $asyncBaiduWorkSourceUpload
     * @param PhotographerWorkSource|null $photographerWorkSource
     */
    static public function QiniuNotifyFetch(
        $msg,
        $request_data,
        AsyncBaiduWorkSourceUpload $asyncBaiduWorkSourceUpload = null,
        PhotographerWorkSource $photographerWorkSource = null
    ) {
        if ($asyncBaiduWorkSourceUpload) {
            $asyncBaiduWorkSourceUpload->status = 500;
            $asyncBaiduWorkSourceUpload->save();
            $asyncBaiduWorkSourceUpload_id = $asyncBaiduWorkSourceUpload->id;
        } else {
            $asyncBaiduWorkSourceUpload_id = 0;
        }
        if ($photographerWorkSource) {
            $photographerWorkSource->status = 500;
            $photographerWorkSource->save();
        }
        $log_filename = 'logs/qiniu_notify_fetch_error/'.date('Y-m-d').'/'.date('H').'.log';
        $error = [];
        $error['log_time'] = date('i:s');
        $error['asyncBaiduWorkSourceUpload_id'] = $asyncBaiduWorkSourceUpload_id;
        $error['msg'] = $msg;
        $error['response'] = $request_data;

        return SystemServer::filePutContents(
            $log_filename,
            json_encode($error, JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }

    /**
     * 持久化报错记录
     * @param $step 第几步
     * @param $msg 报错信息
     * @param $request_data 请求数据
     * @param PhotographerWorkSource|null $photographerWorkSource
     * @param $res 返回数据
     */
    static public function QiniuNotifyFop(
        $step,
        $msg,
        $request_data = [],
        PhotographerWorkSource $photographerWorkSource = null,
        $res = []
    ) {
        if ($photographerWorkSource) {
            $photographerWorkSource->status = 500;
            $photographerWorkSource->save();
            $photographerWorkSource_id = $photographerWorkSource->id;
        } else {
            $photographerWorkSource_id = 0;
        }
        $log_filename = 'logs/qiniu_notify_fop_error/'.date('Y-m-d').'/'.date('H').'.log';
        $error = [];
        $error['log_time'] = date('i:s');
        $error['photographerWorkSource_id'] = $photographerWorkSource_id;
        $error['step'] = $step;
        $error['msg'] = $msg;
        $error['request'] = $request_data;
        $error['res'] = $res;

        return SystemServer::filePutContents(
            $log_filename,
            json_encode($error, JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }

    /**
     * 发送微信公众号模板消息错误日志
     * @param $template_id
     * @param $msg
     * @param $remark
     * @return bool|int
     */
    public static function SendWxGhTemplateMessage($template_id, $msg, $remark)
    {
        $log_filename = 'logs/send_wx_gh_template_message_error/'.date('Y-m-d').'/'.date('H').'.log';
        $error = [];
        $error['log_time'] = date('i:s');
        $error['template_id'] = $template_id;
        $error['msg'] = $msg;
        $error['remark'] = $remark;

        return SystemServer::filePutContents(
            $log_filename,
            json_encode($error, JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }
}
