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
        $form = $request->input('form');
        $size = 20;
        $page = ($page - 1) * $size;


        $where = [];
        if ($form['type'] != 0) {
            $where[] = ['type', $form['type']];
        }

        if ($form['status'] != -1) {
            $where[] = ['status', $form['status']];
        }

        if (isset($form['created_at'][0])) {
            $where[] = array("created_at", ">=", $form['created_at'][0] . ' 00:00:01');
        }
        if (isset($form['created_at'][1])) {
            $where[] = array("created_at", "<=", $form['created_at'][1] . ' 23:59:59');
        }

        $data = InvoteCode::where($where)->skip($page)->take($size)->orderBy('created_at', 'desc')->get();

        $count = InvoteCode::where($where)->count();

        foreach ($data as &$datum) {
            $datum['type'] = $datum['type'] == 1 ? '用户创建' : '后台创建';
            $datum['status'] = $statusArr[$datum['status']] ?? '未知';
        }
        return response()->json(compact('data', 'count'));
    }

    public function store(Request $request)
    {
        $number = intval($request->input('number'));
        for ($i = 0; $i < $number; $i++) {
            $invoteCode = new InvoteCode();
            $invoteCode->code = substr($i . uniqid(), 0, 6);
            $invoteCode->type = 2;
            $invoteCode->status = 0;
            $invoteCode->user_id = 0;
            $invoteCode->order_id = 0;
            $invoteCode->created_at = date('Y-m-d H:i:s');
            $invoteCode->save();
        }

        return response()->json([
            'result' => true,
            'msg' => '生成成功'
        ]);

    }
}
