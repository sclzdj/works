<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Exception;
use Log;
use App\Servers\BaiduServer;
use App\Model\Index\DeliverWorkSyncPanJob;
use App\Model\Index\DeliverWorkFile;
use App\Model\Index\DeliverWork;
use App\Model\Index\DeliverWorkObtain;

class SyncPan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $deliverWorkSyncPanJob;

    const SYNC_STATUS_WAITING = 0;
    const SYNC_STATUS_PROGRESSING = 1;
    const SYNC_STATUS_SUCCESS = 2;
    const SYNC_STATUS_FAILED = 3;

    /**
     * 任务可以尝试的最大次数。
     *
     * @var int
     */
    public $tries = 3;
    /**
     * 任务可以执行的秒数 (超时时间)，需要比retry_after短
     *
     * @var int
     */
    public $timeout = 3590;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(DeliverWorkSyncPanJob $deliverWorkSyncPanJob)
    {
        $this->deliverWorkSyncPanJob = $deliverWorkSyncPanJob;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $deliverWorkSyncPanJob = $this->deliverWorkSyncPanJob;

        //更改任务状态为处理中
        $deliverWorkSyncPanJob->status = self::SYNC_STATUS_PROGRESSING;
        $deliverWorkSyncPanJob->save();

        //根据workId获取文件列表
        $workId = $deliverWorkSyncPanJob->work_id;
        $obtainId = $deliverWorkSyncPanJob->obtain_id;
        $accessToken = $deliverWorkSyncPanJob->access_token;

        Log::info("SyncPan handle start, workID[{$workId}], obtainId[{$obtainId}]");

        $fileList = DeliverWorkFile::where(['work_id' => $workId, 'is_dir' => 0])->get();

        $client = new \GuzzleHttp\Client(['verify' => false]);  //忽略SSL错误
        foreach ($fileList as $file) {
            //将文件下载到本地
            $localStoragePath = storage_path("app") . '/' . $file->object_key;

            try {
                $client->get($file->url, ['save_to' => $localStoragePath]);  //保存远程url到文件
            } catch (Exception $e) {
                //retry
                $client->get($file->url, ['save_to' => $localStoragePath]);  //保存远程url到文件
                Log::info("download file retry, dlink:" . $file->url);
            }

            //将文件上传到网盘
            $panStoragePath = $deliverWorkSyncPanJob->dir . '/' . $file->name . '.' . $file->extension;

            $res = BaiduServer::upload($accessToken, $panStoragePath, $localStoragePath);
            if ($res === false) {//retry
                $res = BaiduServer::upload($accessToken, $panStoragePath, $localStoragePath);
                if ($res === false) {//抛出异常
                    @unlink($localStoragePath);
                    $errMsg = BaiduServer::$errMsg;
                    throw new Exception("upload failed, errMsg:" . $errMsg);
                }
            }

            @unlink($localStoragePath);
        }
        //更新任务表状态为成功
        $deliverWorkSyncPanJob->status = self::SYNC_STATUS_SUCCESS;
        $deliverWorkSyncPanJob->save();

        //如果是客户提取，更改提取表是否同步到网盘字段，修改作品表的是否已下载和下载次数
        if ($deliverWorkSyncPanJob->operator_type == 1) {
            //更改提取表是否同步到网盘字段
            DeliverWorkObtain::where('id', $obtainId)->update(['status' => 1, 'is_sync_pan' => 1]);

            //修改作品表的是否已下载和下载次数
            DeliverWork::where('id', $workId)->increment('download_num', 1, ['is_download' => 1]);
        }

        Log::info("SyncPan handle success, workID[{$workId}], obtainId[{$obtainId}]");
    }

    /**
     * 执行失败的任务。
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        //将任务状态改为失败
        $deliverWorkSyncPanJob = $this->deliverWorkSyncPanJob;
        $workId = $deliverWorkSyncPanJob->work_id;
        $obtainId = $deliverWorkSyncPanJob->obtain_id;

        $deliverWorkSyncPanJob->status = self::SYNC_STATUS_FAILED;
        $deliverWorkSyncPanJob->fail_reason = $exception->getMessage();
        $deliverWorkSyncPanJob->save();

        Log::warning("SyncPan exec failed, workID[{$workId}], obtainId[{$obtainId}], exception message:" . $exception->getMessage());
    }
}
