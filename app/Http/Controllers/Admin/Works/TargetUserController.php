<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\HelpNoteRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Index\HelpNote;
use App\Model\Index\HelpTagNotes;
use App\Model\Index\HelpTags;
use App\Model\Index\TargetUser;
use App\Model\Index\Templates;
use App\Servers\ArrServer;
use Illuminate\Http\Request;

class TargetUserController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('/admin/works/target/index');
    }



    public function lists(Request $request)
    {
        $page = $request->input('page', 1);
        $form = $request->input('form' , []);
        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];
        if ($form['sources'] != -1) {
            $where[] = ['target_users.source', $form['sources']];
        }

        if ($form['status'] != -1) {
            $where[] = ['target_users.status', $form['status']];
        }
        $data = TargetUser::where($where)
            ->skip($page)->take($size)
            ->join('invote_codes' , 'invote_codes.id' , '=' , 'target_users.id')
            ->join('users' , 'users.id' , '=' , 'target_users.user_id')
            ->orderBy('created_at', 'desc')
            ->select('target_users.*' ,'invote_codes.code' , 'users.nickname')
            ->get();
        $count = TargetUser::where($where)->count();

        return response()->json(compact('data', 'count'));
    }


    public function store(Request $request)
    {
        $data = $request->input('form');
        $result = TargetUser::where('id', $data['id'])->update([
            'status' => $data['status']
        ]);
        $msg = "";
        return response()->json(compact('result', 'msg'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 0) {
                HelpNote::where('id', $id)->update(['status' => 400]);
                \DB::commit();//提交事务

                return $this->response('删除成功', 200);
            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                HelpNote::whereIn('id', $ids)->update(['status' => 400]);
                \DB::commit();//提交事务

                return $this->response('批量删除成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }


}
