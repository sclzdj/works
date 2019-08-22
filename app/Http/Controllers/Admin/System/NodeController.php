<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\SystemNodeRequest;
use App\Model\Admin\SystemNode;
use App\Servers\ArrServer;
use Illuminate\Http\Request;

class NodeController extends BaseController {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $pid = $request['pid'] !== null ?
          $request['pid'] :
          0;
        $modules = SystemNode::modules();
        $max_level = max(0, (int)$request->max_level);
        $grMaxHtml = SystemNode::grMaxHtml($pid, '', $max_level);

        return view('/admin/system/node/index',
          compact('grMaxHtml', 'modules', 'pid', 'max_level')
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        $pid = $request['pid'] !== null ?
          $request['pid'] :
          0;
        $treeNodes = SystemNode::treeNodes();

        return view('/admin/system/node/create', compact('treeNodes', 'pid'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(SystemNodeRequest $systemNodeRequest) {
        \DB::beginTransaction();//开启事务
        try {
            $data = $systemNodeRequest->all();
            $data = ArrServer::null2strData($data);
            if ($data['pid'] > 0) {
                $pSystemNode = SystemNode::find($data['pid']);
                $data['level'] = $pSystemNode->level + 1;
            } else {
                $data['level'] = 1;
            }

            if ($data['level'] > 4) {
                \DB::rollback();//回滚事务

                return $this->response('本系统最高只支持4级节点', 400);
            }
            if ($data['level'] == 3 || ($data['level'] == 2 && $data['action'] !== '')) {
                action($data['action']);
            }

            $data['pid'] = (int)$data['pid'];
            if ($data['pid'] == 2) {
                \DB::rollback();//提交事务

                return $this->response('系统主页节点下面不能包含子节点', 400);
            }
            $data['status'] = $data['status'] ?? 0;
            $data['sort'] = (int)$data['sort'];
            $data['icon'] = 'fa '.$data['icon'];
            $systemNode = SystemNode::create($data);
            $response = [
              'url' => action('Admin\System\NodeController@index'),
              'id'  => $systemNode->id,
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
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $systemNode = SystemNode::find($id);
        if (!$systemNode || $systemNode->id <= 2) {
            abort(403, '参数无效');
        }
        $treeNodes = SystemNode::treeNodes(0, '', $systemNode);

        return view('/admin/system/node/edit',
          compact('systemNode', 'treeNodes')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(SystemNodeRequest $systemNodeRequest, $id) {
        $systemNode = SystemNode::find($id);
        if (!$systemNode || $systemNode->id <= 2) {
            return $this->response('参数无效', 403);
        }
        \DB::beginTransaction();//开启事务
        try {
            $data = $systemNodeRequest->all();
            $data = ArrServer::null2strData($data);
            if ($data['pid'] == 2) {
                \DB::rollback();//回滚事务

                return $this->response('系统主页节点下面不能包含子节点', 400);
            }
            if ($data['pid'] > 0) {
                $pSystemNode = SystemNode::find($data['pid']);
                $data['level'] = $pSystemNode->level + 1;
            } else {
                $data['level'] = 1;
            }
            if ($data['level'] > 4) {
                \DB::rollback();//回滚事务

                return $this->response('本系统最高只支持4级节点', 400);
            }
            if ($data['level'] == 3 || ($data['level'] == 2 && $data['action'] !== '')) {
                action($data['action']);
            }
            $data['pid'] = (int)$data['pid'];
            $data['status'] = $data['status'] ?? 0;
            $data['sort'] = (int)$data['sort'];
            $data['icon'] = 'fa '.$data['icon'];
            $child_ids = SystemNode::progenyNodes($id, '', 1);
            if ($systemNode->status != $data['status']) {
                if ($data['status']) {
                    $run_ids = SystemNode::elderNodes($id, 1);
                    $run_ids[] = $id;
                } else {
                    $run_ids = $child_ids;
                    $run_ids[] = $id;
                }
                SystemNode::where('id', '>', '2')->whereIn('id', $run_ids)
                          ->update(['status' => $data['status']]);
            }
            if ($systemNode->level != $data['level']) {
                foreach ($child_ids as $v) {
                    $cSystemNode = SystemNode::find($v);
                    if ($systemNode->level + $cSystemNode->level > 4) {
                        \DB::rollback();//回滚事务

                        return $this->response('本系统最高只支持4级节点', 400);
                    }
                    $cSystemNode->update([
                      'level' => $data['level'] -
                        $systemNode->level +
                        $cSystemNode->level,
                    ]
                    );
                }
            }
            $systemNode->update($data);
            $response = [
              'url' => action('Admin\System\NodeController@index'),
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
    public function destroy($id) {
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 2) {
                $run_ids = SystemNode::progenyNodes($id, '', 1);
                $run_ids[] = $id;
                SystemNode::where('id', '>', '2')->whereIn('id', $run_ids)
                          ->delete();
                \DB::table('system_user_nodes')
                   ->whereIn('system_node_id', $run_ids)->delete();
                \DB::table('system_role_nodes')
                   ->whereIn('system_node_id', $run_ids)->delete();
                \DB::commit();//提交事务

                return $this->response('删除成功', 200);
            } else {
                \DB::rollback();//回滚事务

                return $this->Response('系统专属节点不可操作', 400);
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
    public function enable($id) {
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 2) {
                $run_ids = SystemNode::elderNodes($id, 1);
                $run_ids =
                  array_merge($run_ids, SystemNode::progenyNodes($id, '', 1));
                $run_ids[] = $id;
                SystemNode::where('id', '>', '2')->whereIn('id', $run_ids)
                          ->update(['status' => '1']);
                \DB::commit();//提交事务

                return $this->response('启用成功', 200);
            } else {
                \DB::rollback();//回滚事务

                return $this->Response('非法操作', 400);
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
    public function disable($id) {
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 2) {
                $run_ids = SystemNode::progenyNodes($id, '', 1);
                $run_ids[] = $id;
                SystemNode::where('id', '>', '2')->whereIn('id', $run_ids)
                          ->update(['status' => '0']);
                \DB::commit();//提交事务

                return $this->response('禁用成功', 200);
            } else {
                \DB::rollback();//回滚事务

                return $this->Response('系统专属节点不可禁用', 400);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sort(Request $request) {
        \DB::beginTransaction();//开启事务
        try {
            $data = $request->sort_list;
            $pid = $request->pid;
            $level = $pid > 0 ?
              2 :
              1;
            if ($data) {
                $data = SystemNode::parseNodes($data, $pid, $level);
                foreach ($data as $d) {
                    if ($d['id'] == 1) {
                        if ($d['pid'] != 0 || $d['level'] != 1) {
                            \DB::rollback();//提交事务

                            return $this->response('系统模块不可被操作', 400);
                        }
                    } elseif ($d['id'] == 2) {
                        if ($d['pid'] != 1 || $d['level'] != 2) {
                            \DB::rollback();//回滚事务

                            return $this->response('系统主页节点不可被操作', 400);
                        }
                    } elseif ($d['pid'] == 2) {
                        \DB::rollback();//提交事务

                        return $this->response('系统主页节点下面不能包含子节点', 400);
                    }
                    $where = ['id' => $d['id']];
                    unset($d['id']);
                    SystemNode::where($where)->update($d);
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function moduleSort(Request $request) {
        if ($request->method() == 'POST') {
            \DB::beginTransaction();//开启事务
            try {
                $data = $request->ids;
                if ($data) {
                    foreach ($data as $k => $v) {
                        SystemNode::where('id', $v)->update(['sort' => $k + 1]);
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
        $modules = SystemNode::modules();

        return view('/admin/system/node/module_sort', compact('modules'));
    }
}
