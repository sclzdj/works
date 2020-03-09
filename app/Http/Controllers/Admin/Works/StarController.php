<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\CrowdFunding;
use App\Model\Index\CrowdFundingLog;
use App\Model\Index\Photographer;
use App\Model\Index\Star;
use Illuminate\Http\Request;

class StarController extends BaseController
{
    /**
     * 大咖列表视图
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin/works/star/index');
    }

    /**
     * 大咖列表
     *
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request)
    {
        $page = $request->input('page', 1);
        $size = 20;
        $page = ($page - 1) * $size;
        $where = [];
        $data = (new Star())
            ->skip($page)->take($size)
            ->leftJoin(
                'photographers',
                'photographers.id',
                '=',
                'stars.photographer_id')
            ->orderBy('stars.sort', 'desc')
            ->orderBy('stars.id', 'desc')
            ->get();

        $count = (new Star())->leftJoin(
            'photographers',
            'photographers.id',
            '=',
            'stars.photographer_id')->count();
        $stars = Photographer::where('status', 200)->get();
        return response()->json(compact('data', 'count', 'stars'));
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
