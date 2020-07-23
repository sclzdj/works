<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\HelpNoteRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Index\HelpNote;
use App\Model\Index\HelpTagNotes;
use App\Model\Index\HelpTags;
use App\Model\Index\InvoteCode;
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
        $form = $request->input('form', []);
        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];
        if ($form['sources'] != -1) {
            $where[] = ['target_users.source', $form['sources']];
        }

        if ($form['status'] == 1) {
            $where[] = ['target_users.invote_code_id', '!=', 0];
        } elseif ($form['status'] == 2)
            $where[] = ['target_users.invote_code_id', 0];


        $data = TargetUser::where($where)
            ->skip($page)->take($size)
            ->leftJoin('invote_codes', 'invote_codes.id', '=', 'target_users.invote_code_id')
            ->leftJoin('users', 'users.id', '=', 'target_users.user_id')
            ->leftJoin('photographer_ranks', 'photographer_ranks.id', '=', 'target_users.rank_id')
            ->orderBy('created_at', 'desc')
            ->select('target_users.*', 'invote_codes.code', 'invote_codes.type as invote_type',
                'invote_codes.status as invote_status',
                'users.nickname', 'users.phoneNumber',
                'users.city',
                'users.province', 'users.gender', 'users.photographer_id',
                'photographer_ranks.name as rank_name'
            )
            ->get();

        foreach ($data as &$datum) {
            if ($datum['status'] == 0 && $datum['works_info']) {
                $workinfo = json_decode($datum['works_info'] , 1);
                $img = array_column($workinfo , 'url');
                $datum['works_info'] = json_encode($img);
            }
        }

        $count = TargetUser::where($where)->count();

        return response()->json(compact('data', 'count'));
    }


    public function store(Request $request)
    {
        $data = $request->input('form');
        $type = $request->input("type");


        switch ($type) {
            case "createInvote":

                if (TargetUser::find($data['id'])->invote_code_id != 0) {
                    $result = false;
                    $msg = "用户已经分配的创建码";
                    break;
                }

                $invoteCode = $this->createInvote($data['user_id']);
                $result = TargetUser::where('id', $data['id'])->update([
                    'invote_code_id' => $invoteCode
                ]);
                $msg = "";
                $data = [
                    'invote_code_id' => $invoteCode,
                    'code' => InvoteCode::find($invoteCode)->code,
                ];
                break;
        }


//        $result = TargetUser::where('id', $data['id'])->update([
//            'status' => $data['status']
//        ]);
//        $msg = "";
        return response()->json(compact('result', 'msg', 'data'));
    }

    private function createInvote($user_id)
    {
        InvoteCode::insert(
            [
                "code" => substr(InvoteCode::str_Rand(6), 0, 6),
                "type" => 3,
                "user_id" => $user_id,
                "order_id" => 0,
                "used_count" => 1,
                "created_at" => date('Y-m-d H:i:s'),
                "status" => 1,
            ]
        );
        return \DB::connection()->getPdo()->lastInsertId();
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
                TargetUser::where('id', $id)->delete();
                \DB::commit();//提交事务

                return response()->json([
                    'result' => true,
                    'msg' => '删除成功',
                ]);

            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                TargetUser::whereIn('id', $ids)->delete();
                \DB::commit();//提交事务

                return response()->json([
                    'result' => true,
                    'msg' => '批量删除成功',
                ]);


            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务
            return response()->json([
                'result' => false,
                'msg' => $e->getMessage(),
            ]);
        }
    }


}
