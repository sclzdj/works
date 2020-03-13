<?php

namespace App\Servers;

use App\Model\Index\AsyncBaiduWorkSourceUpload;
use App\Model\Index\PhotographerWork;
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
        $filePutContents = SystemServer::filePutContents(
            $log_filename,
            json_encode($error, JSON_UNESCAPED_UNICODE).PHP_EOL
        );
        if ($photographerWorkSource) {
            //错误处理机制
            $photographerWorkSources = PhotographerWorkSource::where(
                [
                    'photographer_work_id'=>$photographerWorkSource->photographer_work_id,
                    'type' => 'image',
                    'status' => 200,
                ]
            )->orderBy('sort','asc')->get();
            foreach ($photographerWorkSources as $source) {
                PhotographerWorkSource::editRunGenerateWatermark($source->id, '错误处理机制');
            }
        }

        return $filePutContents;
    }

    /**
     * 发送微信公众号模板消息错误日志
     * @param $template_id
     * @param $gh_openid
     * @param $msg
     * @param $remark
     * @param bool $command
     * @return bool|int 是否是用命令发送
     */
    public static function SendWxGhTemplateMessage($template_id, $gh_openid, $msg, $remark, $command = false)
    {
        $log_filename = '';
        if ($command) {
            $log_filename = 'public/';
        }
        $log_filename .= 'logs/send_wx_gh_template_message_error/'.date('Y-m-d').'/'.date('H').'.log';
        $error = [];
        $error['log_time'] = date('i:s');
        $error['template_id'] = $template_id;
        $error['gh_openid'] = $gh_openid;
        $error['msg'] = $msg;
        $error['remark'] = $remark;

        return SystemServer::filePutContents(
            $log_filename,
            json_encode($error, JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }

    /**
     * 用命令发送微信公众号模板消息错误日志
     * @param $template_id
     * @param $gh_openid
     * @param $msg
     * @param $remark
     * @return bool|int
     */
    public static function SendWxGhTemplateMessageCommand($template_id, $gh_openid, $msg, $remark)
    {
        return self::SendWxGhTemplateMessage($template_id, $gh_openid, $msg, $remark, true);
    }
}
