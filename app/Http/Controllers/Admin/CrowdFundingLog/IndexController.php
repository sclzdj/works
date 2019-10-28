<?php

namespace App\Http\Controllers\Admin\CrowdFundingLog;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\CrowdFunding;
use App\Model\Index\CrowdFundingLog;
use App\Model\Index\InvoteCode;
use Illuminate\Http\Request;


class IndexController extends BaseController
{
    /**
     * 众筹列表视图
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin/crowdfundinglog/index');
    }

    /**
     * 众筹列表
     *
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request)
    {
        $statusArr = ['未使用', '已参与'];
        $page = $request->input('page', 1);
        $form = $request->input('form');
        $size = 20;
        $page = ($page - 1) * $size;
        $where = [];

        if ($form['status'] != -1)
            $where[] = ['crowd_status', $form['status']];


        $data = CrowdFundingLog::where($where)
            ->skip($page)->take($size)
            ->leftJoin('users', 'users.id', '=', 'crowd_funding_logs.user_id')
            ->orderBy('created_at', 'desc')
            ->select(['crowd_funding_logs.*' , 'users.phoneNumber'])
            ->get();
        $count = CrowdFundingLog::where($where)->count();

        foreach ($data as &$datum) {
            $datum['crowd_status'] = $statusArr[$datum['crowd_status']] ?? '未知';
        }
        return response()->json(compact('data', 'count'));
    }
}
