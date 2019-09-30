<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\VisitRequest;
use App\Model\Index\OperateRecord;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\User;
use App\Model\Index\ViewRecord;
use App\Model\Index\Visitor;
use App\Model\Index\VisitorTag;
use App\Servers\ArrServer;
use App\Servers\SystemServer;

/**
 * 访问相关
 * Class VisitController
 * @package App\Http\Controllers\Api
 */
class VisitController extends UserGuardController
{
    /**
     * 保存进入页面的操作记录
     * @param VisitRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function inRecord(VisitRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $user = auth($this->guard)->user();
            $photographer = Photographer::where(['id' => $request->photographer_id, 'status' => 200])->first();
            if (!$photographer) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographer_user = User::where(['identity' => 1, 'photographer_id' => $request->photographer_id])->first(
            );
            if (!$photographer_user) {
                return $this->response->error('摄影师信息错误', 500);
            }
            $request->photographer_work_id = $request->photographer_work_id ?? 0;
            if ($request->photographer_work_id > 0) {
                $photographer_work = PhotographerWork::where(
                    ['id' => $request->photographer_work_id, 'photographer_id' => $photographer->id, 'status' => 200]
                )->first();
                if (!$photographer_work) {
                    return $this->response->error('摄影师作品集不存在', 500);
                }
            }
            ViewRecord::where(
                ['user_id' => $user->id, 'photographer_id' => $request->photographer_id]
            )->delete();
            $view_record = ViewRecord::create();
            $view_record->user_id = $user->id;
            $view_record->photographer_id = $request->photographer_id;
            $view_record->save();
            $operate_record = OperateRecord::create();
            $operate_record->user_id = $user->id;
            $operate_record->page_name = $request->page_name;
            $operate_record->photographer_id = $request->photographer_id;
            $operate_record->photographer_work_id = $request->photographer_work_id;
            $operate_record->in_type = $request->in_type;
            $operate_record->shared_user_id = $request->shared_user_id ?? 0;
            $operate_record->operate_type = 'in';
            $operate_record->save();
            $operate_record = OperateRecord::create();
            $operate_record->user_id = $user->id;
            $operate_record->page_name = $request->page_name;
            $operate_record->photographer_id = $request->photographer_id;
            $operate_record->photographer_work_id = $request->photographer_work_id;
            $operate_record->operate_type = 'view';
            $operate_record->save();
            if ($user->id != $photographer_user->id) {//如果不是自己访问，记录访客信息
                $visitor = Visitor::where(
                    ['photographer_id' => $request->photographer_id, 'user_id' => $user->id]
                )->first();
                if (!$visitor) {
                    $visitor = Visitor::create();
                    $visitor->photographer_id = $request->photographer_id;
                    $visitor->user_id = $user->id;
                }
                $visitor->unread_count = $visitor->unread_count + 2;
                $visitor->save();
            }
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存分享的操作记录
     * @param VisitRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function shareRecord(VisitRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $user = auth($this->guard)->user();
            $photographer = Photographer::where(['id' => $request->photographer_id, 'status' => 200])->first();
            if (!$photographer) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographer_user = User::where(['identity' => 1, 'photographer_id' => $request->photographer_id])->first(
            );
            if (!$photographer_user) {
                return $this->response->error('摄影师信息错误', 500);
            }
            $request->photographer_work_id = $request->photographer_work_id ?? 0;
            if ($request->photographer_work_id > 0) {
                $photographer_work = PhotographerWork::where(
                    ['id' => $request->photographer_work_id, 'photographer_id' => $photographer->id, 'status' => 200]
                )->first();
                if (!$photographer_work) {
                    return $this->response->error('摄影师作品集不存在', 500);
                }
            }
            $operate_record = OperateRecord::create();
            $operate_record->user_id = $user->id;
            $operate_record->page_name = $request->page_name;
            $operate_record->photographer_id = $request->photographer_id;
            $operate_record->photographer_work_id = $request->photographer_work_id;
            $operate_record->share_type = $request->share_type;
            $operate_record->operate_type = 'share';
            $operate_record->save();
            if ($user->id != $photographer_user->id) {//如果不是自己访问，记录访客信息
                $visitor = Visitor::where(
                    ['photographer_id' => $request->photographer_id, 'user_id' => $user->id]
                )->first();
                if (!$visitor) {
                    $visitor = Visitor::create();
                    $visitor->photographer_id = $request->photographer_id;
                    $visitor->user_id = $user->id;
                }
                $visitor->unread_count++;
                $visitor->save();
            }
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存复制微信的操作记录
     * @param VisitRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function copyWxRecord(VisitRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $user = auth($this->guard)->user();
            $photographer = Photographer::where(['id' => $request->photographer_id, 'status' => 200])->first();
            if (!$photographer) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographer_user = User::where(['identity' => 1, 'photographer_id' => $request->photographer_id])->first(
            );
            if (!$photographer_user) {
                return $this->response->error('摄影师信息错误', 500);
            }
            $operate_record = OperateRecord::create();
            $operate_record->user_id = $user->id;
            $operate_record->page_name = 'photographer_home';
            $operate_record->photographer_id = $request->photographer_id;
            $operate_record->operate_type = 'copy_wx';
            $operate_record->save();
            if ($user->id != $photographer_user->id) {//如果不是自己访问，记录访客信息
                $visitor = Visitor::where(
                    ['photographer_id' => $request->photographer_id, 'user_id' => $user->id]
                )->first();
                if (!$visitor) {
                    $visitor = Visitor::create();
                    $visitor->photographer_id = $request->photographer_id;
                    $visitor->user_id = $user->id;
                }
                $visitor->unread_count++;
                $visitor->save();
            }
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 设置访客提醒
     * @param VisitRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function setRemind(VisitRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            $visitor = Visitor::where(
                ['id' => $request->visitor_id, 'photographer_id' => $photographer->id]
            )->first();
            if (!$visitor || !(User::find($visitor->user_id))) {
                return $this->response->error('访客不存在', 500);
            }
            $visitor->is_remind = $request->is_remind;
            $visitor->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 设置访客标签
     * @param VisitRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function setTag(VisitRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            $visitor = Visitor::where(
                ['id' => $request->visitor_id, 'photographer_id' => $photographer->id]
            )->first();
            if (!$visitor || !(User::find($visitor->user_id))) {
                return $this->response->error('访客不存在', 500);
            }
            $visitor->visitor_tag_id = $request->visitor_tag_id;
            $visitor->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 访客查询
     * @param VisitRequest $request
     * @return mixed
     */
    public function visitors(VisitRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $fields = array_map(
            function ($v) {
                return 'visitors.'.$v;
            },
            Visitor::allowFields()
        );
        $Visitor = Visitor::join('users', 'visitors.user_id', '=', 'users.id')->select($fields)->where(
            ['visitors.photographer_id' => $photographer->id]
        );
        if (!empty($request->visitor_tag_id) && $request->visitor_tag_id > 0) {
            $Visitor->where('visitors.visitor_tag_id', $request->visitor_tag_id);
        }
        if (!empty($request->keywords)) {
            $Visitor->where('users.nickname', 'like', '%'.$request->keywords.'%');
        }
        $visitors = $Visitor->paginate(
            $request->pageSize
        );
        $visitors = SystemServer::parsePaginate($visitors->toArray());
        foreach ($visitors['data'] as $k => $visitor) {
            $operateRecord = OperateRecord::where(
                ['user_id' => $visitor['user_id'], 'photographer_id' => $visitor['photographer_id']]
            )->orderBy('created_at', 'desc')->orderBy("id", "desc")->first();
            $describe = '';
            if ($operateRecord) {
                $describe = $this->_makeDescribe($operateRecord->id);
            }
            $visitors['data'][$k]['describe'] = $describe;
            $visitors['data'][$k]['user'] = User::select(User::allowFields())->where('id', $visitor['user_id'])->first(
            )->toArray();
        }
        $visitors['data'] = SystemServer::parseVisitorTag($visitors['data']);

        return $this->response->array($visitors);
    }

    /**
     * 访问总未读数查询
     * @return mixed
     */
    public function unreadCount()
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $all_unread_count = Visitor::where(['photographer_id' => $photographer->id])->sum('unread_count');

        return $this->responseParseArray(compact('all_unread_count'));
    }

    /**
     * 访客标签列表
     * @return mixed
     */
    public function tags()
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $visitors = Visitor::select('visitor_tag_id')->distinct()->where(
            [['visitors.photographer_id', '=', $photographer->id], ['visitor_tag_id', '!=', 0]]
        )->get();
        $visitor_tag_ids = ArrServer::ids($visitors, 'visitor_tag_id');
        $tags = VisitorTag::select(VisitorTag::allowFields())->whereIn('id', $visitor_tag_ids)->orderBy(
            'sort',
            'asc'
        )->get()->toArray();

        return $this->responseParseArray($tags);
    }

    /**
     * 访客详情
     * @param VisitRequest $request
     * @return mixed
     */
    public function visitor(VisitRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            $visitor = Visitor::select(Visitor::allowFields())->where(
                ['id' => $request->visitor_id, 'photographer_id' => $photographer->id]
            )->first();
            if (!$visitor) {
                \DB::rollback();//回滚事务

                return $this->response->error('访客信息有误', 500);
            }
            $visitor->unread_count = 0;//未读置0
            $visitor->save();
            $visitor = $visitor->toArray();
            unset($visitor['unread_count']);
            $visitor['user'] = User::select(User::allowFields())->where('id', $visitor['user_id'])->first()->toArray();
            \DB::commit();//提交事务
            $visitor = SystemServer::parseVisitorTag($visitor);

            return $this->responseParseArray($visitor);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 访客的记录数据
     * @param VisitRequest $request
     * @return mixed
     */
    public function visitorRecords(VisitRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $user = auth($this->guard)->user();
        $photographer = User::photographer(null, $this->guard);
        $visitor = Visitor::select(Visitor::allowFields())->where(
            ['id' => $request->visitor_id, 'photographer_id' => $photographer->id]
        )->first();
        if (!$visitor) {
            \DB::rollback();//回滚事务

            return $this->response->error('访客信息有误', 500);
        }
        $page = $request->page ?? 0;
        $pageSize = $request->pageSize ?? 15;
        $view_records = OperateRecord::where('photographer_id', $photographer->id)->where(
            'user_id',
            $visitor->user_id
        )->selectRaw('DATE(created_at) as date,COUNT(id) as total')->groupBy('date')->orderBy(
            "date",
            "desc"
        )->orderBy("id", "desc")->skip(($page - 1) * $pageSize)->take($pageSize)->get()->toArray();
        foreach ($view_records as $k => $view_record) {
            $records = OperateRecord::where('photographer_id', $photographer->id)->where(
                'user_id',
                $visitor->user_id
            )->select(OperateRecord::allowFields())->whereDate(
                'created_at',
                $view_record['date']
            )->orderBy('created_at', 'desc')->get()->toArray();
            foreach ($records as $_k => $record) {
                $records[$_k]['user'] = User::select(User::allowFields())->where(['id' => $record['user_id']])->first(
                )->toArray();
                $records[$_k]['photographer'] = Photographer::select(Photographer::allowFields())->where(
                    ['id' => $record['photographer_id']]
                )->first()->toArray();
                $records[$_k]['photographer_work'] = [];
                if ($record['page_name'] == 'photographer_work') {
                    $records[$_k]['photographer_work'] = PhotographerWork::select(
                        PhotographerWork::allowFields()
                    )->where(['id' => $record['photographer_work_id']])->first()->toArray();
                }
                $records[$_k]['shared_user'] = [];
                if ($record['in_type'] == 'share_in') {
                    $records[$_k]['shared_user'] = User::select(User::allowFields())->where(
                        ['id' => $record['shared_user_id']]
                    )->first()->toArray();
                }
                $records[$_k]['time'] = date('H:i:s', strtotime($record['created_at']));
                $records[$_k]['describe'] = $this->_makeDescribe($record['id']);
                $records[$_k] = [
                    'time' => $records[$_k]['time'],
                    'describe' => $records[$_k]['describe'],
                ];
            }
            $view_records[$k]['view_records'] = $records;
        }

        return $this->responseParseArray($view_records);
    }

    /**
     * 生成访问记录描述
     * @param $operate_id
     * @return string
     */
    private function _makeDescribe($operate_id)
    {
        $operateRecord = OperateRecord::where(['id' => $operate_id])->first();
        $visitor_nickname = (string)User::where('id', $operateRecord->user_id)->value('nickname');
        $photographer_name = (string)Photographer::where('id', $operateRecord->photographer_id)->value('name');
        $photographer_work_customer_name = (string)PhotographerWork::where(
            'id',
            $operateRecord->photographer_work_id
        )->value('customer_name');
        $shared_user_nickname = (string)User::where('id', $operateRecord->shared_user_id)->value('nickname');
        $describe = '';
        if ($operateRecord->operate_type == 'view') {
            if ($operateRecord->page_name == 'photographer_home') {
                $describe = $visitor_nickname.'浏览了主页';
            } elseif ($operateRecord->page_name == 'photographer_work') {
                $describe = $visitor_nickname.'浏览了【'.$photographer_work_customer_name.'】';
            }
        } elseif ($operateRecord->operate_type == 'in') {
            if ($operateRecord->in_type == 'xacode_in') {
                $describe = $visitor_nickname.'扫描小程序码进入';
            } elseif ($operateRecord->in_type == 'xacard_in') {
                $describe = $visitor_nickname.'通过'.$shared_user_nickname.'分享的小程序卡片进入';
            } elseif ($operateRecord->in_type == 'view_history_in') {
                $describe = $visitor_nickname.'从最近浏览进入';
            } elseif ($operateRecord->in_type == 'routine_in') {
                $describe = $visitor_nickname.'通过普通方式进入';
            }
        } elseif ($operateRecord->operate_type == 'share') {
            if ($operateRecord->page_name == 'photographer_home') {
                if ($operateRecord->share_type == 'xacard_share') {
                    $describe = $visitor_nickname.'将主页分享给了微信好友';
                } elseif ($operateRecord->share_type == 'poster_share') {
                    $describe = $visitor_nickname.'生成了主页的海报';
                }
            } elseif ($operateRecord->page_name == 'photographer_work') {
                if ($operateRecord->share_type == 'xacard_share') {
                    $describe = $visitor_nickname.'将【'.$photographer_work_customer_name.'】分享给了微信好友';
                } elseif ($operateRecord->share_type == 'poster_share') {
                    $describe = $visitor_nickname.'生成了【'.$photographer_work_customer_name.'】的海报';
                }
            }
        } elseif ($operateRecord->operate_type == 'copy_wx') {
            $describe = $visitor_nickname.'复制了微信号';
        }

        return $describe;
    }
}
