<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\CrowdFunding;
use App\Model\Index\CrowdFundingLog;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\Star;
use App\Model\Index\Templates;
use Illuminate\Http\Request;

class TemplatesController extends BaseController
{

    public function index()
    {
        return view('admin/templates/index');
    }

    public function lists(Request $request)
    {
        $page = $request->input('page', 1);
        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];
        $data = Templates::where($where)->skip($page)->take($size)->orderBy('created_at', 'desc')->get();
        $count = Templates::where($where)->count();

        foreach ($data as &$datum) {
            $datum['type'] = $datum['type'] == 1 ? '用户创建' : '后台创建';
            $datum['status'] = $statusArr[$datum['status']] ?? '未知';
        }

        return response()->json(compact('data', 'count'));
    }

    public function store()
    {

    }

    public function show()
    {

    }

    public function destroy($id)
    {
        $result = Templates::where('id', $id)->delete();
        return response()->json(compact('result'));
    }


    public function create()
    {
        return view('admin/templates/create');
    }


}
