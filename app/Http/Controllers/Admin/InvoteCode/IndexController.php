<?php

namespace App\Http\Controllers\Admin\InvoteCode;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\InvoteCode;
use Illuminate\Http\Request;


class IndexController extends BaseController
{
    /**
     * 邀请码页面
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin/invotecode/index');
    }

    /**
     * 邀请码列表
     * @param page 页数
     * @form array 查询参数
     *
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request)
    {
        $statusArr = ['未使用', '已占用', '已使用'];
        $page = $request->input('page', 1);
        $form = $request->input('form');
        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];
        if ($form['type'] != 0)
            $where[] = ['type', $form['type']];

        if ($form['status'] != -1)
            $where[] = ['status', $form['status']];

        if (isset($form['created_at'][0]))
            $where[] = array("created_at", ">=", $form['created_at'][0] . ' 00:00:01');

        if (isset($form['created_at'][1]))
            $where[] = array("created_at", "<=", $form['created_at'][1] . ' 23:59:59');

        $data = InvoteCode::where($where)->skip($page)->take($size)->orderBy('created_at', 'desc')->get();
        $count = InvoteCode::where($where)->count();

        foreach ($data as &$datum) {
            $datum['type'] = $datum['type'] == 1 ? '用户创建' : '后台创建';
            $datum['status'] = $statusArr[$datum['status']] ?? '未知';
        }
        return response()->json(compact('data', 'count'));
    }

    /**
     * 创建邀请码
     * @param number 创建的个数
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $action = $request->input('action');
        switch ($action) {
            case 'create':
                $number = intval($request->input('number'));
                for ($i = 0; $i < $number; $i++) {
                    $invoteCode = new InvoteCode();
                    $invoteCode->code = substr($i . $this->str_Rand(3) . mt_rand(0, 9999), 0, 6);
                    $invoteCode->type = 2;
                    $invoteCode->status = 0;
                    $invoteCode->user_id = 0;
                    $invoteCode->order_id = 0;
                    $invoteCode->created_at = date('Y-m-d H:i:s');
                    $invoteCode->save();
                }
                break;
            case 'update':
                $id = intval($request->input('id'));
                InvoteCode::where('id', $id)->update(['status' => 1]);
                break;
            default:
                break;
        }
        return response()->json([
            'result' => true,
            'msg' => '生成成功'
        ]);
    }

    /**
     * 创建随机数
     * @param int $length 随机数长度
     * @return string
     */
    private function str_Rand($length)
    {
        $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
        return substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), $length);
    }
}
