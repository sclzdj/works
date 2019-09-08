<?php

namespace App\Jobs;

use App\Model\Index\AsyncDocPdfMake;
use App\Model\Index\AsyncDocPdfMakeErrorLog;
use App\Model\Index\DocPdf;
use App\Model\Index\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AsyncDocPdfMakeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $asyncDocPdfMake;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(AsyncDocPdfMake $asyncDocPdfMake)
    {
        $this->asyncDocPdfMake = $asyncDocPdfMake;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $asyncDocPdfMake = $this->asyncDocPdfMake;
        $user = User::where(['id' => $asyncDocPdfMake->user_id])->first();
        if (!$user) {
            return $this->errorHandle('用户未找到');
        }
        if ($user['identity'] != 1) {
            return $this->errorHandle('该用户不是摄影师');
        }
        $photographer = User::photographer($user->photographer_id);
        if (!$photographer) {
            return $this->errorHandle('摄影师不存在');
        }
        $doc_pdf = DocPdf::where(['id' => $asyncDocPdfMake->doc_pdf_id, 'status' => 0])->first();
        if (!$doc_pdf) {
            return $this->errorHandle('PDF不存在');
        }
        sleep(10);//需要处理
        $doc_pdf->url = '';
        $doc_pdf->status = 200;
        $doc_pdf->save();
    }

    protected function errorHandle($error_info)
    {
        $asyncDocPdfMake = $this->asyncDocPdfMake;
        $asyncDocPdfMake->status = 500;
        $asyncDocPdfMakeErrorLog = AsyncDocPdfMakeErrorLog::create();
        $asyncDocPdfMakeErrorLog->async_doc_pdf_make_id = $asyncDocPdfMake->id;
        $asyncDocPdfMakeErrorLog->error_info = $error_info;

        $asyncDocPdfMake->save();
        $asyncDocPdfMakeErrorLog->save();
    }
}
