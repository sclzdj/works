<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Admin\SystemConfig;
use App\Servers\ArrServer;
use Illuminate\Http\Request;

class IndexController extends BaseController
{
    public function config(Request $request)
    {
        if ($request->method() == 'POST') {
            \DB::beginTransaction();//开启事务
            try {
                $data = $request->all();
                $data = ArrServer::null2strData($data);
                foreach ($data as $k => $v) {
                    SystemConfig::where(['type' => 'works', 'name' => $k])->update(
                        ['value' => $v]
                    );
                }
                \DB::commit();//提交事务

                return $this->response('设置成功', 200);

            } catch (\Exception $e) {
                \DB::rollback();//回滚事务

                return $this->eResponse($e->getMessage(), 500);
            }
        }
        $systemConfigs = SystemConfig::where(['type' => 'works'])->get();

        return view('/admin/works/index/config', compact('systemConfigs'));
    }
}
