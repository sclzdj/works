<?php


namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\Photographer;
use App\Model\Index\Question;
use App\Model\Index\Star;
use Illuminate\Http\Request;

class QuestionController extends BaseController
{
    /**
     * 问题反馈
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin/question/index');
    }

    /**
     * 大咖列表
     *
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request)
    {
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
            $where[] = array("created_at", ">=", $form['created_at'][0].' 00:00:01');
        }

        if (isset($form['created_at'][1])) {
            $where[] = array("created_at", "<=", $form['created_at'][1].' 23:59:59');
        }



        $data = (new Question())
            ->where($where)
            ->skip($page)->take($size)
            ->join('users' , 'users.id' , '=' ,'question.user_id')
            ->select('question.*' ,'users.nickname')
            ->get();

        $count = (new Star())->count();

        return response()->json(compact('data', 'count'));
    }

    public function edit($id)
    {
        return view('admin/question/edit' , compact('id'));
    }

    public function store(Request $request)
    {

        if ($request->input('type', null) == "sort") {
            $photographer_id = $request->input('form.photographer_id');
            $result = Star::where(compact('photographer_id'))->update([
                'sort' => $request->input('form.sort')
            ]);
            $msg = "排序完成";
            return response()->json(compact('result', 'msg'));
        }


        $result = false;
        $photographer_id = $request->input('form.status', 0);
        if (empty($photographer_id)) {
            $msg = "用户不存在";
            return response()->json(compact('result', 'msg'));
        }

        if (Star::where(compact('photographer_id'))->first()) {
            $msg = "用户已经存在";
            return response()->json(compact('result', 'msg'));
        }

        $result = Star::insert([
            'photographer_id' => $photographer_id,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $msg = "用户添加成功";
        return response()->json(compact('result', 'msg'));
    }

    public function destroy($id)
    {
        $result = Star::where('photographer_id', $id)->delete();
        return response()->json(compact('result'));
    }

}
