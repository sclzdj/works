<?php

namespace App\Jobs;

use App\Model\Index\AsyncBaiduWorkUpload;
use App\Model\Index\AsyncBaiduWorkUploadErrorLog;
use App\Model\Index\BaiduOauth;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\User;
use App\Servers\SystemServer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AsyncBaiduWorkUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $asyncBaiduWorkUpload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(AsyncBaiduWorkUpload $asyncBaiduWorkUpload)
    {
        $this->asyncBaiduWorkUpload = $asyncBaiduWorkUpload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $asyncBaiduWorkUpload = $this->asyncBaiduWorkUpload;
            if (!$asyncBaiduWorkUpload) {
                return;
            }
            $user = User::where(['id' => $asyncBaiduWorkUpload->user_id])->first();
            if (!$user) {
                return $this->errorHandle('用户未找到');
            }
            $photographerWork = PhotographerWork::where(['id' => $asyncBaiduWorkUpload->photographer_work_id])->first();
            if (!$photographerWork) {
                return $this->errorHandle('摄影师作品集未找到');
            }
            $access_token = BaiduOauth::where(
                [
                    ['user_id', '=', $asyncBaiduWorkUpload->user_id],
                    ['expired_at', '>', date('Y-m-d H:i:s')],
                ]
            )->value('access_token');
            if (!$access_token) {
                return $this->errorHandle('百度网盘未授权或者授权过期');
            }
            $response = SystemServer::baiduPanDownAndUpQiniu($access_token, $asyncBaiduWorkUpload->dlink);
            if ($response['code'] != 200) {
                return $this->errorHandle($response['msg']);
            }
            if (!isset($response['data'][0]['key'])) {
                return $this->errorHandle('上传后七牛未返回key值');
            }
            $asyncBaiduWorkUpload->status = 200;
            $photographerWorkSource = PhotographerWorkSource::create();
            $photographerWorkSource->photographer_work_id = $asyncBaiduWorkUpload->photographer_work_id;
            $photographerWorkSource->url = 'http://pxbe4sb12.bkt.clouddn.com/'.$response['data'][0]['key'];
            if ($asyncBaiduWorkUpload->category == 1) {
                $photographerWorkSource->type = 'video';
            } elseif ($asyncBaiduWorkUpload->category == 3) {
                $photographerWorkSource->type = 'image';
            }
            $photographerWorkSource->init_size = $asyncBaiduWorkUpload->size;
            $photographerWorkSource->type = 'image';
            $photographerWorkSource->origin = 'baidu_disk';
            $photographerWorkSource->sort = $asyncBaiduWorkUpload->sort;

            $asyncBaiduWorkUpload->save();
            $photographerWorkSource->save();
        } catch (\Exception $e) {
            return $this->errorHandle($e->getMessage());
        }
    }

    protected function errorHandle($error_info)
    {
        $asyncBaiduWorkUpload = $this->asyncBaiduWorkUpload;
        $asyncBaiduWorkUpload->status = 500;
        $asyncBaiduWorkUploadErrorLog = AsyncBaiduWorkUploadErrorLog::create();
        $asyncBaiduWorkUploadErrorLog->async_baidu_work_upload_id = $asyncBaiduWorkUpload->id;
        $asyncBaiduWorkUploadErrorLog->error_info = $error_info;

        $asyncBaiduWorkUpload->save();
        $asyncBaiduWorkUploadErrorLog->save();
    }
}
