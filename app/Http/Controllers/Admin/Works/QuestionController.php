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
            $where[] = ['question.type', $form['type']];
        }

        if ($form['status'] != -1) {
            $where[] = ['question.status', $form['status']];
        }

        if ($form['page'] != "选择页面") {
            $where[] = ['question.page', $form['page']];
        }

        if (isset($form['created_at'][0])) {
            $where[] = array("question.created_at", ">=", $form['created_at'][0] . ' 00:00:01');
        }

        if (isset($form['created_at'][1])) {
            $where[] = array("question.created_at", "<=", $form['created_at'][1] . ' 23:59:59');
        }


        $data = (new Question())
            ->where($where)
            ->skip($page)->take($size)
            ->join('users', 'users.id', '=', 'question.user_id')
            ->select('question.*', 'users.nickname')
            ->get();

        $count = (new Star())->count();

        return response()->json(compact('data', 'count'));
    }

    public function edit($id)
    {
        return view('admin/question/edit', compact('id'));
    }

    public function show($id)
    {
        return response()->json([
            'msg' => (new Question())
                ->where('question.id', $id)
                ->join('users', 'users.id', '=', 'question.user_id')
                ->select('question.*', 'users.nickname')
                ->first()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->input('form');
        $result =Question::where('id' , $data['id'])->update([
           'status' => $data['status']
        ]);
        $msg = "";
        return response()->json(compact('result', 'msg'));
    }

    public function destroy($id)
    {
        $result = Star::where('photographer_id', $id)->delete();
        return response()->json(compact('result'));
    }

}
