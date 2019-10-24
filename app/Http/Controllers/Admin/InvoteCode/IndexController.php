<?php

namespace App\Http\Controllers\Admin\InvoteCode;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\InvoteCode;
use Illuminate\Http\Request;


class IndexController extends BaseController
{
    public function index()
    {
        return view('admin/invotecode/index');
    }

    public function lists(Request $request)
    {
        $statusArr = ['未使用', '已占用', '已使用'];
        $page = $request->input('page', 1);
        $size = 5;
        $page = ($page - 1) * $size;
        $count = InvoteCode::count();

        $where = [];
        $data = InvoteCode::where($where)->skip($page)->take($size)->orderBy('created_at', 'desc')->get();

        foreach ($data as &$datum) {
            $datum['type'] = $datum['type'] == 1 ? '用户创建' : '后台创建';
            $datum['status'] = $statusArr[$datum['status']] ?? '未知';
        }
        return response()->json(compact('data', 'count'));
    }
}
