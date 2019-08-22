<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Admin\SystemArea;
use Illuminate\Http\Request;

class AreaController extends BaseController
{
    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $pid = $request['pid'] !== null ?
            $request['pid'] :
            0;
        if ($pid > 0) {
            $elderAreas = SystemArea::elderAreas($pid);
            $elderAreas[] = SystemArea::find($pid);
        } else {
            $elderAreas = [];
        }
        $grMaxHtml = SystemArea::grMaxHtml($pid, '', 1);

        return view('/admin/system/area/index',
                    compact('grMaxHtml', 'pid', 'elderAreas'));
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function sort(Request $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $sort_list = $request['sort_list'];
            $pid = $request['pid'];
            foreach ($sort_list as $k => $v) {
                SystemArea::where(['pid' => $pid, 'id' => $v['id']])
                    ->update(['sort' => $k + 1]);
            }
            \DB::commit();//提交事务

            return $this->response('排序成功', 200);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }
}
