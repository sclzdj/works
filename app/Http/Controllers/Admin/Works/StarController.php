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
                'stars.photographer_id')->get();

        $count =  (new Star())->leftJoin(
                'photographers',
                'photographers.id',
                '=',
                'stars.photographer_id')->count();
        $stars = Photographer::where('status', 200)->get();
        return response()->json(compact('data', 'count', 'stars'));
    }

    public function store(Request $request)
    {
        $result = false;
        $photographer_id = $request->input('form.status', 0);
        if (empty($photographer_id)) {
            $msg = "摄影师不存在";
            return response()->json(compact('result', 'msg'));
        }

        if (Star::where(compact('photographer_id'))->first()) {
            $msg = "摄影师已经存在";
            return response()->json(compact('result', 'msg'));
        }

        $result = Star::insert([
            'photographer_id' => $photographer_id,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $msg = "摄影师添加成功";
        return response()->json(compact('result', 'msg'));
    }

    public function destroy($id)
    {
        $result = Star::where('photographer_id', $id)->delete();
        return response()->json(compact('result'));
    }


}
