<?php

namespace App\Jobs;

use App\Model\Index\PhotographerWork;
use App\Servers\WechatServer;
use Illuminate\Bus\Queueable;
use Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class generateXacode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $photographerWork;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($photographerWorkId)
    {
        //
        $this->photographerWork = PhotographerWork::where(['id' => $photographerWorkId])->first();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        \DB::beginTransaction();
        try{
            $scene = '1/'.$this->photographerWork->id . '/' . $this->photographerWork->photographer_id;
            if (!$this->photographerWork->xacode) {
                $xacode_res = WechatServer::generateXacode($scene, false);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                }
                $this->photographerWork->xacode = $xacode_res['xacode'];
            }
            if (!$this->photographerWork->xacode_hyaline) {
                $xacode_res = WechatServer::generateXacode($scene);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                }
                $this->photographerWork->xacode_hyaline = $xacode_res['xacode'];
            }
        }catch (\Exception $e){
            \DB::rollBack();
        }

        $this->photographerWork->save();

        \DB::commit();



    }
}
