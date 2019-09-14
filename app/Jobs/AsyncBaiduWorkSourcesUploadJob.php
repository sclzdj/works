<?php

namespace App\Jobs;

use App\Model\Index\AsyncBaiduWorkSourcesUpload;
use App\Model\Index\AsyncBaiduWorkSourceUpload;
use App\Model\Index\AsyncBaiduWorkSourceUploadErrorLog;
use App\Model\Index\BaiduOauth;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\User;
use App\Servers\ArrServer;
use App\Servers\SystemServer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AsyncBaiduWorkSourcesUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $asyncBaiduWorkSourcesUpload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(AsyncBaiduWorkSourcesUpload $asyncBaiduWorkSourcesUpload)
    {
        $this->asyncBaiduWorkSourcesUpload = $asyncBaiduWorkSourcesUpload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        set_time_limit(8640000);
        $asyncBaiduWorkSourcesUpload = $this->asyncBaiduWorkSourcesUpload;
        if (!$asyncBaiduWorkSourcesUpload) {
            return;
        }
        if ($asyncBaiduWorkSourcesUpload->status != 0) {
            return;
        }
        $asyncBaiduWorkSourceUploads = AsyncBaiduWorkSourceUpload::where(
            ['async_baidu_work_sources_upload_id' => $asyncBaiduWorkSourcesUpload->id, 'status' => 0]
        )->orderBy('sort', 'asc')->get();
        $asyncBaiduWorkSourceUploadIds = ArrServer::ids($asyncBaiduWorkSourceUploads);
        $user = User::where(['id' => $asyncBaiduWorkSourcesUpload->user_id])->first();
        if (!$user) {
            return $this->errorHandle($asyncBaiduWorkSourceUploadIds, '用户未找到');
        }
        $photographerWork = PhotographerWork::where(
            ['id' => $asyncBaiduWorkSourcesUpload->photographer_work_id]
        )->first();
        if (!$photographerWork) {
            return $this->errorHandle($asyncBaiduWorkSourceUploadIds, '摄影师作品集未找到');
        }
        $asyncBaiduWorkSourcesUpload->status = 200;
        foreach ($asyncBaiduWorkSourceUploads as $asyncBaiduWorkSourceUpload) {
            try {
                $access_token = BaiduOauth::where(
                    [
                        ['user_id', '=', $asyncBaiduWorkSourcesUpload->user_id],
                        ['expired_at', '>', date('Y-m-d H:i:s')],
                    ]
                )->value('access_token');
                if (!$access_token) {
                    $asyncBaiduWorkSourcesUpload->status = 500;
                    $this->errorHandle($asyncBaiduWorkSourceUpload->id, '百度网盘未授权或者授权过期');
                    continue;
                }
                $bucket = 'zuopin';
                $buckets = config('custom.qiniu.buckets');
                $domain = $buckets[$bucket]['domain'] ?? '';
                $response = SystemServer::baiduPanDownAndUpQiniu(
                    $access_token,
                    $asyncBaiduWorkSourceUpload->dlink,
                    $bucket,
                    $asyncBaiduWorkSourceUpload->category,
                    $asyncBaiduWorkSourceUpload->size
                );
                if ($response['code'] != 200) {
                    $asyncBaiduWorkSourcesUpload->status = 500;
                    $this->errorHandle($asyncBaiduWorkSourceUpload->id, $response['msg']);
                    continue;
                }
                if (!isset($response['data']['key'])) {
                    $asyncBaiduWorkSourcesUpload->status = 500;
                    $this->errorHandle($asyncBaiduWorkSourceUpload->id, '上传后七牛未返回key值');
                    continue;
                }
                $photographerWorkSource = PhotographerWorkSource::create();
                $photographerWorkSource->photographer_work_id = $asyncBaiduWorkSourcesUpload->photographer_work_id;
                $photographerWorkSource->key = $response['data']['key'];
                $photographerWorkSource->deal_key = $response['data']['key'];
                $photographerWorkSource->rich_key = $response['data']['key'];
                $photographerWorkSource->url = $domain.'/'.$response['data']['key'];
                $photographerWorkSource->deal_url = $domain.'/'.$response['data']['key'];
                $photographerWorkSource->rich_url = $domain.'/'.$response['data']['key'];
                $photographerWorkSource->init_size = $asyncBaiduWorkSourceUpload->size;
                $photographerWorkSource->deal_size = $asyncBaiduWorkSourceUpload->size;
                $photographerWorkSource->rich_size = $asyncBaiduWorkSourceUpload->size;
                $res = SystemServer::request('GET', $domain.'/'.$response['data']['key'].'?imageInfo');
                if ($res['code'] == 200) {
                    if (!isset($res['data']['code']) || $res['data']['code'] == 200) {
                        $photographerWorkSource->init_size = $res['data']['size'];
                        $photographerWorkSource->deal_size = $res['data']['size'];
                        $photographerWorkSource->rich_size = $res['data']['size'];
                    }
                }
                if ($asyncBaiduWorkSourceUpload->category == 1) {
                    $photographerWorkSource->type = 'video';
                } elseif ($asyncBaiduWorkSourceUpload->category == 3) {
                    $photographerWorkSource->type = 'image';
                }
                $photographerWorkSource->origin = 'baidu_disk';
                $photographerWorkSource->sort = $asyncBaiduWorkSourceUpload->sort;
                $photographerWorkSource->status = 200;
                $photographerWorkSource->save();
                $asyncBaiduWorkSourceUpload->status = 200;
                $asyncBaiduWorkSourceUpload->save();
                $log_filename = 'logs/qiniu_fop_error/'.date('Y-m-d').'/'.date('H').'.log';
                if ($asyncBaiduWorkSourceUpload->category == 1) {
                    $fops = "";
                } elseif ($asyncBaiduWorkSourceUpload->category == 3) {
                    $fops = ["imageMogr2/colorspace/srgb|imageView2/2/w/1200|imageslim"];
                }
                $qrst = SystemServer::qiniuPfop(
                    $bucket,
                    $response['data']['key'],
                    $fops,
                    null,
                    config(
                        'app.url'
                    ).'/api/notify/qiniu/fop?photographer_work_source_id='.$photographerWorkSource->id.'&step=1',
                    true
                );
                if (!empty($qrst['err'])) {
                    $error = [];
                    $error['log_time'] = date('i:s');
                    $error['step'] = 0;
                    $error['msg'] = json_encode($qrst['err']);
                    SystemServer::filePutContents(
                        $log_filename,
                        json_encode($error).PHP_EOL
                    );
                    $photographerWorkSource->status = 500;
                    $photographerWorkSource->save();
                }
            } catch (\Exception $e) {
                $asyncBaiduWorkSourcesUpload->status = 500;
                $this->errorHandle($asyncBaiduWorkSourceUpload->id, $e->getMessage());
                continue;
            }
        }
        $asyncBaiduWorkSourcesUpload->save();

        return;
    }

    protected function errorHandle($async_baidu_work_source_upload_ids, $error_info)
    {
        $asyncBaiduWorkSourcesUpload = $this->asyncBaiduWorkSourcesUpload;
        $asyncBaiduWorkSourcesUpload->status = 500;
        $asyncBaiduWorkSourcesUpload->save();
        if (!is_array($async_baidu_work_source_upload_ids)) {
            $async_baidu_work_source_upload_ids = [$async_baidu_work_source_upload_ids];
        }
        foreach ($async_baidu_work_source_upload_ids as $async_baidu_work_source_upload_id) {
            $asyncBaiduWorkSourceUpload = AsyncBaiduWorkSourceUpload::where(
                'id',
                $async_baidu_work_source_upload_id
            )->first();
            $asyncBaiduWorkSourceUpload->status = 500;
            $asyncBaiduWorkSourceUpload->save();
            $asyncBaiduWorkSourceUploadErrorLog = AsyncBaiduWorkSourceUploadErrorLog::create();
            $asyncBaiduWorkSourceUploadErrorLog->async_baidu_work_source_upload_id = $async_baidu_work_source_upload_id;
            $asyncBaiduWorkSourceUploadErrorLog->error_info = $error_info;
            $asyncBaiduWorkSourceUploadErrorLog->save();
        }
    }
}
