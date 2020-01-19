<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\HelpNoteRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Index\HelpNote;
use App\Servers\ArrServer;
use Illuminate\Http\Request;

class HelpNoteController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];

        $filter = [
//            'id' => $request['id'] !== null ?
//                $request['id'] :
//                '',
            'title' => $request['title'] !== null ?
                $request['title'] :
                '',
//            'content' => $request['content'] !== null ?
//                $request['content'] :
//                '',
//            'created_at_start' => $request['created_at_start'] !== null ?
//                $request['created_at_start'] :
//                '',
//            'created_at_end' => $request['created_at_end'] !== null ?
//                $request['created_at_end'] :
//                '',
        ];
        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                'sort',
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                'asc',
        ];
        $where = [];
//        if ($filter['id'] !== '') {
//            $where[] = ['id', 'like', '%'.$filter['id'].'%'];
//        }
        if ($filter['title'] !== '') {
            $where[] = ['title', 'like', '%'.$filter['title'].'%'];
        }
//        if ($filter['content'] !== '') {
//            $where[] = ['content', 'like', '%'.$filter['content'].'%'];
//        }
//        if ($filter['created_at_start'] !== '' &&
//            $filter['created_at_end'] !== ''
//        ) {
//            $where[] = [
//                'created_at',
//                '>=',
//                $filter['created_at_start']." 00:00:00",
//            ];
//            $where[] = [
//                'created_at',
//                '<=',
//                $filter['created_at_end']." 23:59:59",
//            ];
//        } elseif ($filter['created_at_start'] === '' &&
//            $filter['created_at_end'] !== ''
//        ) {
//            $where[] = [
//                'created_at',
//                '<=',
//                $filter['created_at_end']." 23:59:59",
//            ];
//        } elseif ($filter['created_at_start'] !== '' &&
//            $filter['created_at_end'] === ''
//        ) {
//            $where[] = [
//                'created_at',
//                '>=',
//                $filter['created_at_start']." 00:00:00",
//            ];
//        }
        $helpNotes = HelpNote::where($where)->where(['status' => 200])
            ->orderBy($orderBy['order_field'], $orderBy['order_type'])
            ->paginate($pageInfo['pageSize']);

        return view(
            '/admin/works/help_note/index',
            compact('helpNotes', 'pageInfo', 'orderBy', 'filter')
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('/admin/works/help_note/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(HelpNoteRequest $helpNoteRequest)
    {
        \DB::beginTransaction();//开启事务
        try {
            $data = $helpNoteRequest->all();
            $data = ArrServer::null2strData($data);
            $data['status'] = 200;
            $data['sort'] = ((int)HelpNote::where(['status' => 200])->orderBy('sort', 'desc')->value('sort')) + 1;
            $helpNote = HelpNote::create($data);
            $response = [
                'url' => action('Admin\Works\HelpNoteController@index'),
                'id' => $helpNote->id,
            ];
            \DB::commit();//提交事务

            return $this->response('添加成功', 201, $response);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $helpNote = HelpNote::where(['status' => 200])->find($id);
        if (!$helpNote) {
            abort(403, '参数无效');
        }

        return view(
            '/admin/works/help_note/edit',
            compact('helpNote')
        );
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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function sort(Request $request) {
        if ($request->method() == 'POST') {
            \DB::beginTransaction();//开启事务
            try {
                $data = $request->ids;
                if ($data) {
                    foreach ($data as $k => $v) {
                        HelpNote::where('id', $v)->update(['sort' => $k + 1]);
                    }
                    \DB::commit();//提交事务

                    return $this->response('排序成功', 200);
                } else {
                    \DB::rollback();//提交事务

                    return $this->response('未知请求', 400);
                }
            } catch (\Exception $e) {
                \DB::rollback();//回滚事务

                return $this->eResponse($e->getMessage(), 500);
            }
        }
        $helpNotes = HelpNote::where(['status'=>200])->orderBy('sort','asc')->get();

        return view('/admin/works/help_note/sort', compact('helpNotes'));
    }
}
