<?php

namespace App\Jobs;

use App\Model\Index\User;
use App\Servers\SystemServer;
use Log;
use App\Model\Index\PhotographerWorkSource;
use App\Servers\WechatServer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckImgSecurity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $photographer, $picurl;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($photographer, $picurl)
    {
        //
        $this->photographer = $photographer;
        $this->picurl = $picurl;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $flag = WechatServer::checkContentSecurity($this->picurl, true);
        \DB::beginTransaction();
        try{
            if ($flag){
                $this->photographer->review = 1;
                $this->photographer->avatar = $this->picurl;

            }else{
                $this->photographer->review = 2;

                $message = "您的头像审核不通过，请及时修改";
                $user = User::where(['photographer_id' => $this->photographer->id])->first();

                SystemServer::noticeMessage($message, $user);
            }
            
            $this->photographer->save();
            \Db::commit();
        }catch (\Exception $e){
            \DB::rollBack();

        }

        Log::info("checkPhotographerAvaterSecurity " . $this->picurl);
    }
}
