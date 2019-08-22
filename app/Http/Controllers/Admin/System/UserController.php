<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\SystemUserRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Admin\SystemNode;
use App\Model\Admin\SystemRole;
use App\Model\Admin\SystemUser;
use App\Servers\ArrServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends BaseController
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
                1
        ];

        $filter = [
            'id' => $request['id'] !== null ?
                $request['id'] :
                '',
            'username' => $request['username'] !== null ?
                $request['username'] :
                '',
            'nickname' => $request['nickname'] !== null ?
                $request['nickname'] :
                '',
            'type' => $request['type'] !== null ?
                $request['type'] :
                '',
            'status' => $request['status'] !== null ?
                $request['status'] :
                '',
            'created_at_start' => $request['created_at_start'] !== null ?
                $request['created_at_start'] :
                '',
            'created_at_end' => $request['created_at_end'] !== null ?
                $request['created_at_end'] :
                '',
        ];
        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                'id',
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                'asc',
        ];
        $where = [];
        if ($filter['id'] !== '') {
            $where[] = ['id', 'like', '%' . $filter['id'] . '%'];
        }
        if ($filter['username'] !== '') {
            $where[] = ['username', 'like', '%' . $filter['username'] . '%'];
        }
        if ($filter['nickname'] !== '') {
            $where[] = ['nickname', 'like', '%' . $filter['nickname'] . '%'];
        }
        if ($filter['type'] !== '') {
            $where[] = ['type', '=', $filter['type']];
        }
        if ($filter['status'] !== '') {
            $where[] = ['status', '=', $filter['status']];
        }
        if ($filter['created_at_start'] !== '' &&
            $filter['created_at_end'] !== ''
        ) {
            $where[] = [
                'created_at',
                '>=',
                $filter['created_at_start'] . " 00:00:00"
            ];
            $where[] = [
                'created_at',
                '<=',
                $filter['created_at_end'] . " 23:59:59"
            ];
        } elseif ($filter['created_at_start'] === '' &&
            $filter['created_at_end'] !== ''
        ) {
            $where[] = [
                'created_at',
                '<=',
                $filter['created_at_end'] . " 23:59:59"
            ];
        } elseif ($filter['created_at_start'] !== '' &&
            $filter['created_at_end'] === ''
        ) {
            $where[] = [
                'created_at',
                '>=',
                $filter['created_at_start'] . " 00:00:00"
            ];
        }
        $systemUsers = SystemUser::where($where)
            ->orderBy($orderBy['order_field'], $orderBy['order_type'])
            ->paginate($pageInfo['pageSize']);

        return view('/admin/system/user/index',
                    compact('systemUsers', 'pageInfo', 'orderBy', 'filter'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $systemRoles = SystemRole::get();
        $treeNodes =
            SystemNode::treeNodes(0, '', '', '&nbsp;&nbsp;&nbsp;&nbsp;');

        return view('/admin/system/user/create',
                    compact('systemRoles', 'treeNodes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(SystemUserRequest $systemUserRequest)
    {
        \DB::beginTransaction();//开启事务
        try {
            $data = $systemUserRequest->all();
            $data = ArrServer::null2strData($data);
            $data['password'] = bcrypt($data['password']);
            $data['remember_token'] = str_random(64);
            $data['status'] = $data['status'] ?? 0;
            $systemUser = SystemUser::create($data);
            if ($data['type'] == '1') {
                foreach ($data['system_role_ids'] as $v) {
                    \DB::table('system_user_roles')->insert([
                                                                'system_user_id' => $systemUser->id,
                                                                'system_role_id' => $v,
                                                            ]);
                }
            } elseif ($data['type'] == '2') {
                foreach ($data['system_node_ids'] as $v) {
                    \DB::table('system_user_nodes')->insert([
                                                                'system_user_id' => $systemUser->id,
                                                                'system_node_id' => $v,
                                                            ]);
                }
            }
            $response = [
                'url' => action('Admin\System\UserController@index'),
                'id' => $systemUser->id
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
     * @param  int $id
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
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $systemUser = SystemUser::find($id);
        if (!$systemUser || $systemUser->id == 1) {
            abort(403, '参数无效');
        }
        $systemRoles = SystemRole::get();
        $treeNodes =
            SystemNode::treeNodes(0, '', '', '&nbsp;&nbsp;&nbsp;&nbsp;');

        return view('/admin/system/user/edit',
                    compact('systemUser', 'systemRoles', 'treeNodes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(SystemUserRequest $systemUserRequest, $id)
    {
        $systemUser = SystemUser::find($id);
        if (!$systemUser || $systemUser->id == 1) {
            return $this->response('参数无效', 403);
        }
        \DB::beginTransaction();//开启事务
        try {
            $data = $systemUserRequest->all();
            $data = ArrServer::null2strData($data);
            if ($data['password'] !== '') {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }
            $data['status'] = $data['status'] ?? 0;
            $systemUser->update($data);
            \DB::table('system_user_roles')
                ->where('system_user_id', $systemUser->id)->delete();
            \DB::table('system_user_nodes')
                ->where('system_user_id', $systemUser->id)->delete();
            if ($data['type'] == '1') {
                foreach ($data['system_role_ids'] as $v) {
                    \DB::table('system_user_roles')->insert([
                                                                'system_user_id' => $systemUser->id,
                                                                'system_role_id' => $v,
                                                            ]);
                }
            } elseif ($data['type'] == '2') {
                foreach ($data['system_node_ids'] as $v) {
                    \DB::table('system_user_nodes')->insert([
                                                                'system_user_id' => $systemUser->id,
                                                                'system_node_id' => $v,
                                                            ]);
                }
            }
            $response = [
                'url' => action('Admin\System\UserController@index')
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
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 0) {
                if ($id > 1) {
                    SystemUser::where('id', $id)->delete();
                    \DB::table('system_user_roles')
                        ->where('system_user_id', $id)->delete();
                    \DB::table('system_user_nodes')
                        ->where('system_user_id', $id)->delete();
                    \DB::commit();//提交事务

                    return $this->response('删除成功', 200);
                } else {
                    \DB::rollback();//回滚事务

                    return $this->Response('系统专属账号不可操作', 400);
                }
            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                SystemUser::where('id', '<>', '1')->whereIn('id', $ids)
                    ->delete();
                \DB::table('system_user_roles')->whereIn('system_user_id', $ids)
                    ->delete();
                \DB::table('system_user_nodes')->whereIn('system_user_id', $ids)
                    ->delete();
                \DB::commit();//提交事务

                return $this->response('批量删除成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param         $id
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enable($id, Request $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 0) {
                if ($id > 1) {
                    SystemUser::where('id', $id)->update(['status' => '1']);
                    \DB::commit();//提交事务

                    return $this->response('启用成功', 200);
                } else {
                    \DB::rollback();//回滚事务

                    return $this->Response('非法操作', 400);
                }
            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                SystemUser::where('id', '<>', '1')->whereIn('id', $ids)
                    ->update(['status' => '1']);
                \DB::commit();//提交事务

                return $this->response('批量启用成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param         $id
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disable($id, Request $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 0) {
                if ($id > 1) {
                    SystemUser::where('id', $id)->update(['status' => '0']);
                    \DB::commit();//提交事务

                    return $this->response('禁用成功', 200);
                } else {
                    \DB::rollback();//回滚事务

                    return $this->Response('系统专属账号不可禁用', 400);
                }
            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                SystemUser::where('id', '<>', '1')->whereIn('id', $ids)
                    ->update(['status' => '0']);
                \DB::commit();//提交事务

                return $this->response('批量禁用成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

}
