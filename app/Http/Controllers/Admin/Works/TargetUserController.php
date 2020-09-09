<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\HelpNoteRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Index\HelpNote;
use App\Model\Index\HelpTagNotes;
use App\Model\Index\HelpTags;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\Sources;
use App\Model\Index\TargetUser;
use App\Model\Index\Templates;
use App\Model\Index\User;
use App\Servers\AliSendShortMessageServer;
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

        $filter = [
//            'id' => $request['id'] !== null ?
//                $request['id'] :
//                '',
            'nickname' => $request['nickname'] !== null ?
                $request['nickname'] :
                '',
            'user_id' => $request['userid'] !== null ?
                $request['userid'] :
                '',
            'name' => $request['name'] !== null ?
                $request['name'] :
                '',
            'photographer_id' => $request['photographerid'] !== null ?
                $request['photographerid'] :
                '',
            'mobile' => $request['mobile'] !== null ?
                $request['mobile'] :
                ''
        ];
        if ($filter['name'] !== '') {
            $where[] = ['photographers.name', 'like', '%'.$filter['name'].'%'];
        }
        if ($filter['nickname'] !== '') {
            $where[] = ['users.nickname', 'like', '%'.$filter['nickname'].'%'];
        }
        if ($filter['user_id'] !== '') {
            $where[] = ['users.user_id', '=', $filter['user_id']];
        }
        if ($filter['photographer_id'] !== '') {
            $where[] = ['users.photographer_id', '=', $filter['name']];
        }
        if ($filter['mobile'] !== '') {
            $where[] = ['users.phoneNumber', '=', '%'.$filter['mobile'].'%'];
        }

        if ($form['sources'] != -1) {
            $where[] = ['target_users.source', $form['sources']];
        }

        if ($form['status'] == 1) {
            $where[] = ['target_users.status', 1];
        } elseif ($form['status'] == 2)
            $where[] = ['target_users.status', '!=', 1];

        if ($form['codeStatus'] != -1) {
            $where[] = ['invote_codes.status', $form['codeStatus']];
        }

        if (!empty($form['phone'])) {
            $where[] = ['users.phoneNumber', 'like', '%' . $form['phone'] . '%'];
        }


        if ($request['order']){
            if (substr($request['order'], 0, 1) == '-'){
                $order = [substr($request['order'], 1), 'desc'];
            }else{
                $order = [substr($request['order'], 1), ""];
            }
        }else{
            $order = ["created_at", ""];
        }

        $data = TargetUser::where($where)
            ->skip($page)->take($size)
            ->leftJoin('invote_codes', 'invote_codes.id', '=', 'target_users.invote_code_id')
            ->leftJoin('users', 'users.id', '=', 'target_users.user_id')
            ->leftJoin('photographer_ranks', 'photographer_ranks.id', '=', 'target_users.rank_id')
            ->select('target_users.*', 'invote_codes.code', 'invote_codes.type as invote_type',
                'invote_codes.status as invote_status',
                'invote_codes.type as invote_type',
                'users.nickname', 'users.phoneNumber',
                'users.city',
                'users.province', 'users.gender', 'users.photographer_id',
                'photographer_ranks.name as rank_name',
                \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=users.photographer_id)) as pwcount'),
                \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors')
            )->groupBy('users.photographer_id')->orderBy($order[0], $order[1])->get();

        foreach ($data as &$datum) {
            if ($datum['status'] == 0 && $datum['works_info']) {
                $workinfo = json_decode($datum['works_info'], 1);
                $img = array_column($workinfo, 'url');
                $datum['works_info'] = json_encode($img);
            }
            if (!$datum['is_invite']){
                $datum['status'] = 0; #未受邀
            }else{
                $datum['status'] = 1; #已受邀
            }
            $pw = PhotographerWork::where(['photographer_id' => $datum['photographer_id']])->first();
            if ($pw){
                $datum['status'] = 2; #有作品 已创建
            }else{
                $invote = InvoteCode::where(['user_id' => $datum['user_id']])->first();
                if ($invote){
                    $datum['status'] = 3; #已升级
                }
            }
        }

        $count = TargetUser::where($where)
            ->leftJoin('invote_codes', 'invote_codes.id', '=', 'target_users.invote_code_id')
            ->leftJoin('users', 'users.id', '=', 'target_users.user_id')
            ->count();

        return response()->json(compact('data', 'count'));
    }

    /*** 添加来源
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function sourcestore(Request $request){
        $id = $request->input('id');
        $name = $request->input('name');
        if ($id){
            Sources::where(['id' => $id])->update(['name' => $name]);
        }else{
            $result = Sources::insert([
                'name' => $name
            ]);
        }

        return response()->noContent();
    }

    /**
     * 添加邀请次数
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function addinvite(Request $request){
        $photographer_id = $request['photographer_id '];
        Photographer::where(['id' => $photographer_id])->increment('invite', 3);

        return response()->noContent();
    }

    /***
     * 裂变(全部)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function fission(Request $request)
    {
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];

        $where = [
            ['invite', '<>', 0],
            ['is_setup', '=', 1]
        ];

        if ($request['order']){
            if (substr($request['order'], 0, 1) == '-'){
                $order = [substr($request['order'], 1), 'desc'];
            }else{
                $order = [substr($request['order'], 1), ""];
            }
        }else{
            $order = ["created_at", ""];
        }
        $filter = [
//            'id' => $request['id'] !== null ?
//                $request['id'] :
//                '',
            'nickname' => $request['nickname'] !== null ?
                $request['nickname'] :
                '',
            'user_id' => $request['userid'] !== null ?
                $request['userid'] :
                '',
            'name' => $request['name'] !== null ?
                $request['name'] :
                '',
            'photographer_id' => $request['photographerid'] !== null ?
                $request['photographerid'] :
                '',
            'mobile' => $request['mobile'] !== null ?
                $request['mobile'] :
                '',
            'source' => $request['source'] !== null ?
                $request['source'] :
                '',
            'level' => $request['level'] !== null ?
            $request['level'] :
                    ''
        ];
        if ($filter['name'] !== '') {
            $where[] = ['photographers.name', 'like', '%'.$filter['name'].'%'];
        }
        if ($filter['nickname'] !== '') {
            $where[] = ['users.nickname', 'like', '%'.$filter['nickname'].'%'];
        }
        if ($filter['user_id'] !== '') {
            $where[] = ['users.user_id', '=', $filter['user_id']];
        }
        if ($filter['photographer_id'] !== '') {
            $where[] = ['users.photographer_id', '=', $filter['name']];
        }
        if ($filter['mobile'] !== '') {
            $where[] = ['users.phoneNumber', '=', '%'.$filter['mobile'].'%'];
        }

        if ($filter['source']) {
            $where[] = ['target_users.source', $filter['source']];
        }

        if ($filter['level']) {
            $where[] = ['photographers.level', $filter['level']];
        }

        $count = $allPhotographer = Photographer::where($where)->join(
            'users',
            'users.photographer_id',
            '=',
            'photographers.id'
        )->leftjoin(
            'target_users',
            'target_users.user_id',
            '=',
            'users.id'
        )->leftjoin(
            'photographer_works',
            'users.photographer_id',
            '=',
            'photographer_works.photographer_id'
        )->select(
            'target_users.source',
            'photographers.*',
            'users.nickname',
            \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=users.photographer_id)) as pwcount'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors'),
            'users.phoneNumber',
            'users.id as uid',
            'users.nickname'
        )->groupBy(
            'photographers.id'
        )->count();

        $allPhotographer = Photographer::where($where)->join(
            'users',
            'users.photographer_id',
            '=',
            'photographers.id'
        )->leftjoin(
            'target_users',
            'target_users.user_id',
            '=',
            'users.id'
        )->leftjoin(
            'photographer_works',
            'users.photographer_id',
            '=',
            'photographer_works.photographer_id'
        )->select(
            'target_users.source',
            'target_users.works_info',
            'photographers.*',
            'users.nickname',
            \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=users.photographer_id)) as pwcount'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors'),
            'users.phoneNumber',
            'users.id as uid',
            'users.nickname'
        )->groupBy(
            'photographers.id'
        )->orderBy(
            $order[0], $order[1],
        )->paginate(
            $pageInfo['pageSize']
        )->toArray();

        var_dump($allPhotographer);
        return response()->noContent();
    }

    public function store(Request $request)
    {
        $data = $request->input('form');
        $type = $request->input("type");


        switch ($type) {
            case "createInvote":

                $user = User::where('id', $data['user_id'])->first();
                $invoteCode = $this->createInvote($data['user_id']);
                $result = TargetUser::where('id', $data['id'])->update([
                    'invote_code_id' => $invoteCode,
                    'status' => 1,
                ]);
                $msg = "";
                $data = [
                    'invote_code_id' => $invoteCode,
                    'code' => InvoteCode::find($invoteCode)->code,
                ];
                if ($user->gh_openid) {
                    $app = app('wechat.official_account');
                    $tmr = $app->template_message->send(
                        [
                            'touser' => $user->gh_openid,
                            'template_id' => 'r7dzz9MM_KxzPeZRCdkswGUqMA_AgqgMVZercZ5WMgM',
                            'url' => config('app.url'),
                            'miniprogram' => [
                                'appid' => config('wechat.payment.default.app_id'),
//                            'pagepath' => 'subPage/crouwdPay/crouwdPay',
                            ],
                            'data' => [
                                'first' => '恭喜你获得云作品试用资格！点击此处，即可开始创建',
                                'keyword1' => '通过',
                                'keyword2' => $user->nickname,
                                'keyword3' => $data['code'],
                                'remark' => '备注：云作品客服微信JUSHEKEJI',
                            ],
                        ]
                    );
                }
                $TemplateCodes = config('custom.send_short_message.ali.TemplateCodes');
                $sendePhone = AliSendShortMessageServer::quickSendSms(
                    $user->purePhoneNumber,
                    $TemplateCodes,
                    'send_invite_result',
                    [
                        'name' => $user->nickname,
                        'code' => $data['code'],
                    ]
                );
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
