<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\HelpNoteRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Index\HelpNote;
use App\Model\Index\HelpTagNotes;
use App\Model\Index\HelpTags;
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

        return view('/admin/works/target/inde');
    }



    public function lists(Request $request)
    {
        $id = $request->input('id', 0);
        if ($id) {
            $template = Templates::where(compact('id'))->first();
            return response()->json(compact('template'));
        }
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


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(HelpNoteRequest $helpNoteRequest, $id)
    {
        $helpNote = HelpNote::find($id);
        if (!$helpNote) {
            return $this->response('参数无效', 403);
        }
        \DB::beginTransaction();//开启事务
        try {
            $data = $helpNoteRequest->all();
            $data = ArrServer::null2strData($data);
            $helpNote->update($data);
            $response = [
                'url' => action('Admin\Works\HelpNoteController@index'),
            ];
            \DB::commit();//提交事务


            if (isset($data['tags']) && $data['tags'] && $tags = explode(',', $data['tags'])) {
                (new HelpTagNotes())->where('help_id' , $helpNote->id)->delete();
                foreach ($tags as $tag) {
                    (new HelpTagNotes())->insert([
                        'tags_id' => $tag,
                        'help_id' => $helpNote->id,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }


            return $this->response('修改成功', 200, $response);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
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
