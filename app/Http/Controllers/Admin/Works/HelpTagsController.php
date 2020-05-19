<?php


namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\HelpTags;
use App\Model\Index\Photographer;
use App\Model\Index\Question;
use App\Model\Index\Star;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HelpTagsController extends BaseController
{
    /**
     * 问题反馈
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin/helptags/index');
    }

    /**
     * 大咖列表
     *
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request)
    {

        $id = $request->input('id', 0);
        if ($id) {
            $template = HelpTags::where(compact('id'))->first();
            return response()->json(compact('template'));
        }

        $page = $request->input('page', 1);
        $form = $request->input('form');
        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];
        if (!empty($form['tagsName'])) {
            $where[] = ['name', 'like', '%'.$form['tagsName'] .'%'];
        }


        if (isset($form['created_at'][0])) {
            $where[] = array("created_at", ">=", $form['created_at'][0] . ' 00:00:01');
        }

        if (isset($form['created_at'][1])) {
            $where[] = array("created_at", "<=", $form['created_at'][1] . ' 23:59:59');
        }


        $data = (new HelpTags())
            ->where($where)
            ->skip($page)->take($size)
            ->get();

        $count = (new HelpTags())->count();

        return response()->json(compact('data', 'count'));
    }

    public function edit($id)
    {
        return view('admin/helptags/edit', compact('id'));
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
        $data['created_at'] = date('Y-m-d H:i:s');
        $result = (new HelpTags())->insert($data);
        return response()->json(compact('result', 'msg'));
    }

    public function destroy($id)
    {
        $result = HelpTags::where('id', $id)->delete();
        return response()->json(compact('result'));
    }

    public function create()
    {
        return view('admin/helptags/create');
    }

    public function update(Request $request) {

        $data = $request->input('form');
        $result = HelpTags::where('id', $data['id'])->update([
            'name' => $data['name']
        ]);
        $msg = "";
        return response()->json(compact('result', 'msg'));

    }

}
