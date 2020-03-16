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
use App\Servers\AliSendShortMessageServer;
use App\Servers\ArrServer;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use Illuminate\Support\Facades\Request;

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
                return $this->response->error('用户不存在', 500);
            }
            $photographer_user = User::where(['identity' => 1, 'photographer_id' => $request->photographer_id])->first(
            );
            if (!$photographer_user) {
                return $this->response->error('用户信息错误', 500);
            }
            $request->photographer_work_id = $request->photographer_work_id ?? 0;
            if ($request->photographer_work_id > 0) {
                $photographer_work = PhotographerWork::where(
                    ['id' => $request->photographer_work_id, 'photographer_id' => $photographer->id, 'status' => 200]
                )->first();
                if (!$photographer_work) {
                    return $this->response->error('用户项目不存在', 500);
                }
            }
            if ($user->id == $photographer_user->id) {
                \DB::commit();//提交事务

                return $this->response->noContent();
            }
            ViewRecord::where(
                ['user_id' => $user->id, 'photographer_id' => $request->photographer_id]
            )->update(['is_newest' => 0]);
            $view_record = ViewRecord::create();
            $view_record->user_id = $user->id;
            $view_record->photographer_id = $request->photographer_id;
            $view_record->is_newest = 1;
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
            $operate_record0 = $operate_record;
            $operate_record = OperateRecord::create();
            $operate_record->user_id = $user->id;
            $operate_record->page_name = $request->page_name;
            $operate_record->photographer_id = $request->photographer_id;
            $operate_record->photographer_work_id = $request->photographer_work_id;
            $operate_record->operate_type = 'view';
            $operate_record->save();
            if ($user->id != $photographer_user->id) {//如果不是自己访问，记录访客信息
                $this->_visitorRecord(
                    $request,
                    $user,
                    $photographer_user,
                    $photographer,
                    [$operate_record0, $operate_record]
                );
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
                return $this->response->error('用户不存在', 500);
            }
            $photographer_user = User::where(['identity' => 1, 'photographer_id' => $request->photographer_id])->first(
            );
            if (!$photographer_user) {
                return $this->response->error('用户信息错误', 500);
            }
            $request->photographer_work_id = $request->photographer_work_id ?? 0;
            if ($request->photographer_work_id > 0) {
                $photographer_work = PhotographerWork::where(
                    ['id' => $request->photographer_work_id, 'photographer_id' => $photographer->id, 'status' => 200]
                )->first();
                if (!$photographer_work) {
                    return $this->response->error('用户项目不存在', 500);
                }
            }
            if ($user->id == $photographer_user->id) {
                \DB::commit();//提交事务

                return $this->response->noContent();
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
                $this->_visitorRecord(
                    $request,
                    $user,
                    $photographer_user,
                    $photographer,
                    [$operate_record]
                );
            }
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存操作记录
     * @param VisitRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function operateRecord(VisitRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $user = auth($this->guard)->user();
            $photographer = Photographer::where(['id' => $request->photographer_id, 'status' => 200])->first();
            if (!$photographer) {
                return $this->response->error('用户不存在', 500);
            }
            $photographer_user = User::where(['identity' => 1, 'photographer_id' => $request->photographer_id])->first(
            );
            if (!$photographer_user) {
                return $this->response->error('用户信息错误', 500);
            }
            $request->photographer_work_id = $request->photographer_work_id ?? 0;
            if ($request->photographer_work_id > 0) {
                $photographer_work = PhotographerWork::where(
                    ['id' => $request->photographer_work_id, 'photographer_id' => $photographer->id, 'status' => 200]
                )->first();
                if (!$photographer_work) {
                    return $this->response->error('用户项目不存在', 500);
                }
            }
            if ($user->id == $photographer_user->id) {
                \DB::commit();//提交事务

                return $this->response->noContent();
            }
            $operate_record = OperateRecord::create();
            $operate_record->user_id = $user->id;
            $operate_record->operate_type = $request->operate_type;
            $operate_record->page_name = $request->page_name;
            $operate_record->photographer_id = $request->photographer_id;
            $operate_record->photographer_work_id = $request->photographer_work_id;
            $operate_record->save();
            if ($user->id != $photographer_user->id) {//如果不是自己访问，记录访客信息
                $this->_visitorRecord(
                    $request,
                    $user,
                    $photographer_user,
                    $photographer,
                    [$operate_record]
                );
            }
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 访客记录内置操作
     * @param VisitRequest $request
     * @param User $user
     * @param User $photographer_user
     * @param Photographer $photographer
     * @param $operate_records
     */
    protected function _visitorRecord(
        VisitRequest $request,
        User $user,
        User $photographer_user,
        Photographer $photographer,
        $operate_records
    ) {
        $visit_send_message = ['is' => false, 'num' => 0, 'is_remind' => 0];//是否推送该消息，发模板消息
        $visitor = Visitor::where(
            ['photographer_id' => $request->photographer_id, 'user_id' => $user->id]
        )->first();
        if (!$visitor) {
            $visitors = Visitor::where(['photographer_id' => $request->photographer_id])->orderBy(
                'created_at',
                'desc'
            )->take(3)->get();
            $visitors_count = count($visitors);
            if ($visitors_count >= 2) {
                $is_formal_photographer_old = $photographer_user->is_formal_photographer;
                $photographer_user->is_formal_photographer = 1;
                $photographer_user->save();
                if ($is_formal_photographer_old == 0) {
                    if ($photographer_user->gh_openid != '') {
                        $app = app('wechat.official_account');
                        $template_id = 'tHdD0AN6uWc0DSNn-68ftEZ48AYavkydeCpvN6GCO9U';
                        $tmr = $app->template_message->send(
                            [
                                'touser' => $photographer_user->gh_openid,
                                'template_id' => $template_id,
                                'url' => config('app.url'),
                                'miniprogram' => [
                                    'appid' => config('custom.wechat.mp.appid'),
                                    'pagepath' => 'pages/cameraman/cameraman',//用户控制面板页
                                ],
                                'data' => [
                                    'first' => '快打开云作品，看看你的前3个人脉吧！',
                                    'keyword1' => '已开启',
                                    'keyword2' => '云作品',
                                    'remark' => '',
                                ],
                            ]
                        );
                        if ($tmr['errcode'] != 0) {
                            ErrLogServer::SendWxGhTemplateMessage(
                                $template_id,
                                $photographer_user->gh_openid,
                                $tmr['errmsg'],
                                $tmr
                            );
                        }
                    }
                    if ($photographer->mobile) {//发送短信
                        $third_type = config('custom.send_short_message.third_type');
                        $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
                        if ($third_type == 'ali') {
                            AliSendShortMessageServer::quickSendSms(
                                $photographer->mobile,
                                $TemplateCodes,
                                'service_open',
                                ['name' => $photographer->name]
                            );
                        }
                    }
                }
            }
            $visitor = Visitor::create(['photographer_id' => $request->photographer_id, 'user_id' => $user->id]);
            if ($user->identity == 1) {
                $visitor->visitor_tag_id = 2;//如果访客也为用户标记为同行
            }
            if ($visitors_count + 1 < 3) {
                $visit_send_message['is'] = true;//第一次发送模板消息，发模板消息
                $visit_send_message['num'] = $visitors_count + 1;
            }
        } else {
            if ($visitor->is_remind == 1) {//特别关注，发模板消息
                $visit_send_message['is'] = true;
                $visit_send_message['is_remind'] = 1;
            }
        }
        if ($visit_send_message['is'] && $photographer_user->gh_openid != '') {
            $describes = [];
            foreach ($operate_records as $operate_record) {
                if ($operate_record->operate_type == 'in') {
                    $first_in_operate_record = OperateRecord::where(
                        [
                            'user_id' => $operate_record->user_id,
                            'photographer_id' => $operate_record->photographer_id,
                            'operate_type' => 'in',
                        ]
                    )->orderBy('created_at', 'asc')->first();
                    if ($first_in_operate_record->id != $operate_record->id) {
                        continue;
                    }
                }
                $describes[] = $this->_makeDescribe($operate_record->id);
            }
            $first_text = $photographer->name.'，人脉有新动态，请及时查看。';
            $keyword1_text = $user->nickname;
            $keyword2_text = implode('并', $describes);
            $keyword3_text = $user->purePhoneNumber;
            $miniprogram_pagepath = 'pages/visitorDetails/visitorDetails?id='.$visitor->id;//访客详情页
            if ($visit_send_message['is_remind'] == 0) {
                if ($visit_send_message['num'] == 1) {
                    $first_text = '第一个人脉是谁？嘿嘿，再来两个告诉你！';
                    $keyword1_text = '神秘人物1';
                    $keyword3_text = '***********';
                    $miniprogram_pagepath = 'subPage/share/share';//注册成功分享页
                } elseif ($visit_send_message['num'] == 2) {
                    $first_text = '叮咚！又来一个，距离开启只有一步之遥。';
                    $keyword1_text = '神秘人物2';
                    $keyword3_text = '***********';
                    $miniprogram_pagepath = 'subPage/share/share';//注册成功分享页
                }
            } elseif ($visit_send_message['is_remind'] == 1) {
                $first_text = '你特别关注的人脉有新动态！';
            }
            $app = app('wechat.official_account');
            $template_id = 'RlRlrXRWpeONZZvu-HT1xQ1EhTvDbucp6Z60AgcQdGs';
            $tmr = $app->template_message->send(
                [
                    'touser' => $photographer_user->gh_openid,
                    'template_id' => $template_id,
                    'url' => config('app.url'),
                    'miniprogram' => [
                        'appid' => config('custom.wechat.mp.appid'),
                        'pagepath' => $miniprogram_pagepath,
                    ],
                    'data' => [
                        'first' => $first_text,
                        'keyword1' => $keyword1_text,
                        'keyword2' => $keyword2_text,
                        'keyword3' => $keyword3_text,
                        'keyword4' => date('Y/m/d H:i'),
                        'remark' => '点击查看详情',
                    ],
                ]
            );
            if ($tmr['errcode'] != 0) {
                ErrLogServer::SendWxGhTemplateMessage(
                    $template_id,
                    $photographer_user->gh_openid,
                    $tmr['errmsg'],
                    $tmr
                );
            }
        }
        if ($visit_send_message['is'] && $photographer->mobile) {//发送短信
            $purpose = '';
            if ($visit_send_message['is_remind'] == 0) {
                if ($visit_send_message['num'] == 1) {
                    $purpose = 'visit_remind_1';
                } elseif ($visit_send_message['num'] == 2) {
                    $purpose = 'visit_remind_2';
                }
            }
            if ($purpose) {
                $third_type = config('custom.send_short_message.third_type');
                $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
                if ($third_type == 'ali') {
                    AliSendShortMessageServer::quickSendSms(
                        $photographer->mobile,
                        $TemplateCodes,
                        $purpose
                    );
                }
            }
        }
        $visitor->last_operate_record_at = date('Y-m-d H:i:s');
        $visitor->save();
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
            $photographer = $this->_photographer(null, $this->guard);
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
            $photographer = $this->_photographer(null, $this->guard);
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
     * 访客筛选条目列表
     * @return mixed
     */
    public function filterItems()
    {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
        $filterItems = [];
        $visitor = Visitor::where(
            ['visitors.photographer_id' => $photographer->id, 'is_remind' => 1]
        )->first();
        if ($visitor) {
            $filterItems[] = [
                'id' => '-1',
                'name' => '特别关注',
            ];
        }
        $visitor = Visitor::where(
            ['visitors.photographer_id' => $photographer->id, 'visitor_tag_id' => 0]
        )->first();
        if ($visitor) {
            $filterItems[] = [
                'id' => '0',
                'name' => '未分组',
            ];
        }
        $visitors = Visitor::select('visitor_tag_id')->distinct()->where(
            [['visitors.photographer_id', '=', $photographer->id], ['visitor_tag_id', '!=', 0]]
        )->get();
        $visitor_tag_ids = ArrServer::ids($visitors, 'visitor_tag_id');
        $tags = VisitorTag::select(['id', 'name'])->whereIn('id', $visitor_tag_ids)->orderBy(
            'sort',
            'asc'
        )->get()->toArray();
        if ($tags) {
            $filterItems = array_merge($filterItems, $tags);
        }

        return $this->responseParseArray($filterItems);
    }

    /**
     * 访客列表
     * @param VisitRequest $request
     * @return mixed
     */
    public function visitors(VisitRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        if ($request->filterItem_id !== null) {
            if ($request->filterItem_id == -1) {
                $request->visitor_tag_id = null;
                $request->is_remind = 1;
            } else {
                $request->visitor_tag_id = $request->filterItem_id;
                $request->is_remind = null;
            }
        }
        $photographer = $this->_photographer(null, $this->guard);
        $fields = array_map(
            function ($v) {
                return 'visitors.'.$v;
            },
            Visitor::allowFields()
        );
        $Visitor = Visitor::join('users', 'visitors.user_id', '=', 'users.id')->select($fields)->where(
            ['visitors.photographer_id' => $photographer->id]
        );
        if ($request->is_remind !== null) {
            $Visitor->where('visitors.is_remind', $request->is_remind);
        }
        if ($request->visitor_tag_id !== null && $request->visitor_tag_id >= 0) {
            $Visitor->where('visitors.visitor_tag_id', $request->visitor_tag_id);
        }
        if ($request->keywords !== null && $request->keywords !== '') {
            $Visitor->where('users.nickname', 'like', '%'.$request->keywords.'%');
        }
        $visitors = $Visitor->orderBy('visitors.last_operate_record_at', 'desc')->orderBy(
            'visitors.created_at',
            'desc'
        )->paginate(
            $request->pageSize
        );
        $visitors = SystemServer::parsePaginate($visitors->toArray());
        foreach ($visitors['data'] as $k => $visitor) {
            $visitors['data'][$k]['unread_count'] = OperateRecord::where(
                ['user_id' => $visitor['user_id'], 'photographer_id' => $visitor['photographer_id'], 'is_read' => 0]
            )->where('operate_type', '!=', 'in')->count();
            $operateRecord = OperateRecord::where(
                ['user_id' => $visitor['user_id'], 'photographer_id' => $visitor['photographer_id']]
            )->where('operate_type', '!=', 'in')->orderBy('created_at', 'desc')->orderBy("id", "desc")->first();
            $describe = '';
            if ($operateRecord) {
                $describe = $this->_makeDescribe($operateRecord->id);
            }
            $visitors['data'][$k]['describe'] = $describe;
            $visitor_tag_type = 0;//未知
            $read_count = OperateRecord::where(
                ['user_id' => $visitor['user_id'], 'photographer_id' => $visitor['photographer_id'], 'is_read' => 1]
            )->count();
            if ($read_count == 0) {
                $visitor_tag_type = 1;//新客
            } else {
                if ($visitor['visitor_tag_id'] > 0) {
                    if (VisitorTag::where('id', $visitor['visitor_tag_id'])->first()) {
                        $visitor_tag_type = 2;//标签
                    }
                }
            }
            $visitors['data'][$k]['visitor_tag_type'] = $visitor_tag_type;
            $visitors['data'][$k]['user'] = User::select(User::allowFields())->where('id', $visitor['user_id'])->first(
            )->toArray();
            $operateRecord = OperateRecord::where(
                [
                    'user_id' => $visitor['user_id'],
                    'photographer_id' => $visitor['photographer_id'],
                    'operate_type' => 'in',
                ]
            )->orderBy('created_at', 'asc')->orderBy("id", "asc")->first();
            $visitors['data'][$k]['first_in_operate_record'] = $this->_generateFirstInOperateRecord($operateRecord);
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
        $photographer = $this->_photographer(null, $this->guard);
        $all_unread_count = OperateRecord::where(['photographer_id' => $photographer->id, 'is_read' => 0])->where(
            'operate_type',
            '!=',
            'in'
        )->count();

        return $this->responseParseArray(compact('all_unread_count'));
    }

    /**
     * 访客标签列表
     * @return mixed
     */
    public function tags()
    {
        $this->notPhotographerIdentityVerify();
        $photographer = $this->_photographer(null, $this->guard);
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
            $photographer = $this->_photographer(null, $this->guard);
            $visitor = Visitor::select(Visitor::allowFields())->where(
                ['id' => $request->visitor_id, 'photographer_id' => $photographer->id]
            )->first();
            if (!$visitor) {
                \DB::rollback();//回滚事务

                return $this->response->error('访客信息有误', 500);
            }
            $visitor->save();
            OperateRecord::where(
                ['user_id' => $visitor->user_id, 'photographer_id' => $photographer->id, 'is_read' => 0]
            )->update(['is_read' => 1]);
            $visitor = $visitor->toArray();
            $visitor['user'] = User::select(User::allowFields())->where('id', $visitor['user_id'])->first()->toArray();
            $operateRecord = OperateRecord::where(
                [
                    'user_id' => $visitor['user_id'],
                    'photographer_id' => $visitor['photographer_id'],
                    'operate_type' => 'in',
                ]
            )->orderBy('created_at', 'asc')->orderBy("id", "asc")->first();
            $visitor['first_in_operate_record'] = $this->_generateFirstInOperateRecord($operateRecord);
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
        $photographer = $this->_photographer(null, $this->guard);
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
        )->where('operate_type', '!=', 'in')->selectRaw('DATE(created_at) as date,COUNT(id) as total')->groupBy(
            'date'
        )->orderBy(
            "date",
            "desc"
        )->orderBy("id", "desc")->skip(($page - 1) * $pageSize)->take($pageSize)->get()->toArray();
        foreach ($view_records as $k => $view_record) {
            $records = OperateRecord::where('photographer_id', $photographer->id)->where(
                'user_id',
                $visitor->user_id
            )->where('operate_type', '!=', 'in')->select(OperateRecord::allowFields())->whereDate(
                'created_at',
                $view_record['date']
            )->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get()->toArray();
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
                $records[$_k]['time'] = date('H:i', strtotime($record['created_at']));
                $records[$_k]['describe'] = $this->_makeDescribe($record['id']);
                $records[$_k] = [
                    'time' => $records[$_k]['time'],
                    'describe' => $records[$_k]['describe'],
                ];
            }
            $view_records[$k]['date'] = date('Y/m/d', strtotime($view_record['date']));
            $view_records[$k]['view_records'] = $records;
        }

        return $this->responseParseArray($view_records);
    }

    /**
     * 生成访问记录描述
     * @param $operate_id
     * @param $is_special 是否为特殊处理记录
     * @return string
     */
    private function _makeDescribe($operate_id, $is_special = false)
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
                $describe = '浏览了你的主页';
            } elseif ($operateRecord->page_name == 'photographer_work') {
                $describe = '浏览了「'.$photographer_work_customer_name.'」';
            }
        } elseif ($operateRecord->operate_type == 'in') {
            if ($operateRecord->in_type == 'xacode_in') {
                if ($operateRecord->page_name == 'photographer_home') {
                    $describe = '扫描你的主页小程序码进入';
                } elseif ($operateRecord->page_name == 'photographer_work') {
                    $describe = '扫描「'.$photographer_work_customer_name.'」的小程序码进入';
                }
            } elseif ($operateRecord->in_type == 'xacard_in') {
                $auth_id = auth($this->guard)->id();
                if ($operateRecord->shared_user_id == $auth_id) {
                    $describe = '通过我分享的小程序卡片进入';
                } else {
                    if ($is_special) {
                        $describe = '通过XX分享的小程序卡片进入';
                    } else {
                        $describe = '通过'.$shared_user_nickname.'分享的小程序卡片进入';
                    }
                }
            } elseif ($operateRecord->in_type == 'ranking_list_in') {
                $describe = '通过「人脉排行榜」进入';
            } elseif ($operateRecord->in_type == 'big_shot_used_in') {
                $describe = '通过「大咖都在用」进入';
            } elseif ($operateRecord->in_type == 'view_history_in') {
                $describe = '通过「最近浏览」进入';
            } elseif ($operateRecord->in_type == 'routine_in') {
                $describe = '通过普通方式进入';
            }
        } elseif ($operateRecord->operate_type == 'share') {
            if ($operateRecord->page_name == 'photographer_home') {
                if ($operateRecord->share_type == 'xacard_share') {
                    $describe = '将你的主页分享给了微信好友';
                } elseif ($operateRecord->share_type == 'poster_share') {
                    $describe = '生成了你的主页海报';
                }
            } elseif ($operateRecord->page_name == 'photographer_work') {
                if ($operateRecord->share_type == 'xacard_share') {
                    $describe = '将「'.$photographer_work_customer_name.'」分享给了微信好友';
                } elseif ($operateRecord->share_type == 'poster_share') {
                    $describe = '生成了「'.$photographer_work_customer_name.'」的海报';
                } elseif ($operateRecord->share_type == 'all_photo_share') {
                    $describe = '保存了「'.$photographer_work_customer_name.'」的所有照片';
                }
            }
        } elseif ($operateRecord->operate_type == 'copy_wx') {
            $describe = '复制了你的微信号';
        } elseif ($operateRecord->operate_type == 'view_project_amount') {
            $describe = '查看了「'.$photographer_work_customer_name.'」的项目金额';
        }

        return $describe;
    }

    /**
     * 生成首次进入记录
     * @param OperateRecord $operateRecord
     */
    public function _generateFirstInOperateRecord(OperateRecord $operateRecord)
    {
        if ($operateRecord) {
            $auth_id = auth($this->guard)->id();
            $first_in_operate_record = [
                'in_type' => $operateRecord->in_type,
                'date' => date('Y/m/d', strtotime($operateRecord->created_at)),
                'describe' => $this->_makeDescribe($operateRecord->id, true),
            ];
            if ($operateRecord->in_type == 'xacard_in') {
                if ($auth_id == $operateRecord->shared_user_id) {
                    $shared_user_is_me = 1;
                } else {
                    $shared_user_is_me = 0;
                }
                $first_in_operate_record['shared_user_is_me'] = $shared_user_is_me;
                if (!$shared_user_is_me) {
                    $shared_visitor_id = (int)Visitor::where(
                        [
                            'user_id' => $operateRecord->shared_user_id,
                            'photographer_id' => $operateRecord->photographer_id,
                        ]
                    )->value('id');
                    $shared_user = User::select(User::allowFields())->where(
                        'id',
                        $operateRecord->shared_user_id
                    )->first();
                    $first_in_operate_record['shared_visitor_id'] = $shared_visitor_id;
                    $first_in_operate_record['shared_user'] = $shared_user;
                }
            }
        } else {
            $first_in_operate_record = [
                'in_type' => '',
                'date' => '',
                'describe' => '',
            ];
        }

        return $first_in_operate_record;
    }
}
