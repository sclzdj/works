<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\SystemRoleRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Admin\SystemNode;
use App\Model\Admin\SystemRole;
use App\Servers\ArrServer;
use App\Servers\NavigationServer;
use Illuminate\Http\Request;

class RoleController extends BaseController
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
            'name' => $request['name'] !== null ?
                $request['name'] :
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
        if ($filter['name'] !== '') {
            $where[] = ['name', 'like', '%' . $filter['name'] . '%'];
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
        $systemRoles = SystemRole::where($where)
            ->orderBy($orderBy['order_field'], $orderBy['order_type'])
            ->paginate($pageInfo['pageSize']);

        return view('/admin/system/role/index',
                    compact('systemRoles', 'pageInfo', 'orderBy', 'filter'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $treeNodes =
            SystemNode::treeNodes(0, '', '', '&nbsp;&nbsp;&nbsp;&nbsp;');

        return view('/admin/system/role/create', compact('treeNodes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(SystemRoleRequest $systemRoleRequest)
    {
        \DB::beginTransaction();//开启事务
        try {
            $data = $systemRoleRequest->all();
            $data = ArrServer::null2strData($data);
            $data['status'] = $data['status'] ?? 0;
            $systemRole = SystemRole::create($data);
            foreach ($data['system_node_ids'] as $v) {
                \DB::table('system_role_nodes')->insert([
                                                            'system_role_id' => $systemRole->id,
                                                            'system_node_id' => $v,
                                                        ]);
            }
            $response = [
                'url' => action('Admin\System\RoleController@index'),
                'id' => $systemRole->id
            ];
            \DB::commit();//提交事务

            return $this->response('添加成功', 201, $response);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
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
        $systemRole = SystemRole::find($id);
        if (!$systemRole) {
            abort(403, '参数无效');
        }
        $treeNodes =
            SystemNode::treeNodes(0, '', '', '&nbsp;&nbsp;&nbsp;&nbsp;');

        return view('/admin/system/role/edit',
                    compact('systemRole', 'treeNodes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(SystemRoleRequest $systemRoleRequest, $id)
    {
        $systemRole = SystemRole::find($id);
        if (!$systemRole) {
            return $this->response('参数无效', 403);
        }
        \DB::beginTransaction();//开启事务
        try {
            $data = $systemRoleRequest->all();
            if ($systemRole->id == 1) {
                $data['name'] = $systemRole->name;
            }
            $data = ArrServer::null2strData($data);
            $data['status'] = $data['status'] ?? 0;
            $systemRole->update($data);
            \DB::table('system_role_nodes')
                ->where('system_role_id', $systemRole->id)->delete();
            foreach ($data['system_node_ids'] as $v) {
                \DB::table('system_role_nodes')->insert([
                                                            'system_role_id' => $systemRole->id,
                                                            'system_node_id' => $v,
                                                        ]);
            }
            $response = [
                'url' => action('Admin\System\RoleController@index')
            ];
            \DB::commit();//提交事务

            return $this->response('修改成功', 200, $response);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
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
                    SystemRole::where('id', $id)->delete();
                    \DB::table('system_role_nodes')
                        ->where('system_role_id', $id)->delete();
                    \DB::commit();//提交事务

                    return $this->response('删除成功', 200);
                } else {
                    \DB::rollback();//回滚事务

                    return $this->Response('系统专属角色不可操作', 400);
                }
            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                SystemRole::where('id', '<>', '1')->whereIn('id', $ids)
                    ->delete();
                \DB::table('system_role_nodes')->whereIn('system_role_id', $ids)
                    ->delete();
                \DB::commit();//提交事务

                return $this->response('批量删除成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
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
                    SystemRole::where('id', $id)->update(['status' => '1']);
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
                SystemRole::where('id', '<>', '1')->whereIn('id', $ids)
                    ->update(['status' => '1']);
                \DB::commit();//提交事务

                return $this->response('批量启用成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
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
                    SystemRole::where('id', $id)->update(['status' => '0']);
                    \DB::commit();//提交事务

                    return $this->response('禁用成功', 200);
                } else {
                    \DB::rollback();//回滚事务

                    return $this->Response('系统专属角色不可禁用', 400);
                }
            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                SystemRole::where('id', '<>', '1')->whereIn('id', $ids)
                    ->update(['status' => '0']);
                \DB::commit();//提交事务

                return $this->response('批量禁用成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }
}
