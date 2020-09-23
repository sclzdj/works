<?php

namespace App\Jobs;

use App\Model\Index\PhotographerWorkSource;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AsynchronousTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $asynchronous_task;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($asynchronous_task)
    {
        //
        $this->asynchronous_task = $asynchronous_task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->asynchronous_task as $task) {
            if ($task['task_type'] == 'qiniuPfop') {
                $qrst = SystemServer::qiniuPfop(
                    $task['bucket'],
                    $task['key'],
                    $task['fops'],
                    $task['pipeline'],
                    $task['notifyUrl'],
                    $task['useHTTPS']
                );
                if ($qrst['err']) {
                    ErrLogServer::qiniuNotifyFop(
                        $task['error_step'],
                        $task['error_msg'],
                        $task['error_request_data'],
                        $task['error_photographerWorkSource'],
                        $qrst['err']
                    );
                }
            } elseif ($task['task_type'] == 'editRunGenerateWatermark') {
                PhotographerWorkSource::editRunGenerateWatermark(
                    $task['photographer_work_source_id'],
                    $task['edit_node']
                );
            } elseif ($task['task_type'] == 'error_qiniuNotifyFop') {
                ErrLogServer::qiniuNotifyFop(
                    $task['step'],
                    $task['msg'],
                    $task['request_data'],
                    $task['photographerWorkSource'],
                    $task['res']
                );
            }
        }
    }
}
