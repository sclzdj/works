<?php

namespace App\Http\Controllers\Admin\InvoteCode;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\InvoteCode;
use App\Model\Index\User;
use App\Servers\AliSendShortMessageServer;
use Illuminate\Http\Request;
use Log;


class IndexController extends BaseController
{
    /**
     * 邀请码页面
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin/invotecode/index');
    }

    /**
     * 邀请码列表
     * @param page 页数
     * @form array 查询参数
     *
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request)
    {
        $statusArr = ['未使用', '已占用', '已使用'];
        $page = $request->input('page', 1);
        $form = $request->input('form');
        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];
        if ($form['type'] != 0) {
            $where[] = ['type', $form['type']];
        }

        if ($form['status'] != -1) {
            $where[] = ['status', $form['status']];
        }

        if ($form['is_send'] != -1) {
            $where[] = ['is_send', $form['is_send']];
        }

        if (isset($form['created_at'][0])) {
            $where[] = array("created_at", ">=", $form['created_at'][0].' 00:00:01');
        }

        if (isset($form['created_at'][1])) {
            $where[] = array("created_at", "<=", $form['created_at'][1].' 23:59:59');
        }

        $data = InvoteCode::where($where)->skip($page)->take($size)->orderBy('created_at', 'desc')->get();
        $count = InvoteCode::where($where)->count();

        foreach ($data as &$datum) {
            $datum['type'] = $datum['type'] == 1 ? '用户创建' : '后台创建';
            $datum['status'] = $statusArr[$datum['status']] ?? '未知';
        }

        return response()->json(compact('data', 'count'));
    }

    /**
     * 创建邀请码
     * @param number 创建的个数
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $action = $request->input('action');
        switch ($action) {
            case 'create':
                $number = intval($request->input('number'));

                if ($number > 30) {
                    return response()->json(
                        [
                            'result' => false,
                            'msg' => '一次最多生成30个邀请码',
                        ]
                    );
                }

                for ($i = 0; $i < $number; $i++) {
                    $invoteCode = new InvoteCode();
                    $invoteCode->code = substr($i.$this->str_Rand(3).mt_rand(0, 9999), 0, 6);
                    $invoteCode->type = 2;
                    $invoteCode->status = 0;
                    $invoteCode->user_id = 0;
                    $invoteCode->order_id = 0;
                    $invoteCode->created_at = date('Y-m-d H:i:s');
                    $invoteCode->save();
                }
                break;
            case 'update':
                $id = intval($request->input('id'));
                InvoteCode::where('id', $id)->update(['status' => 1]);
                break;
            case 'send':
                $app = app('wechat.official_account');
                $datas = InvoteCode::where(
                    [
                        'type' => 1,
                        'is_send' => 0,
                    ]
                )->whereIn('status', [0, 1])->get();

                foreach ($datas as $data) {
                    if ($data['user_id'] == 0) {
                        continue;
                    }
                    $userInfo = User::where('id', $data['user_id'])->first();
                    if ($userInfo->gh_openid) {
                        $tmr = $app->template_message->send(
                            [
                                'touser' => $userInfo->gh_openid,
                                'template_id' => 'D0A51QNL0-EI_fA65CJQQ4LKotXelLNoATCwtpvwFco',
                                'miniprogram' => [
                                    'appid' => config('wechat.payment.default.app_id'),
                                    'pagepath' => '/pages/web/web',
                                ],
                                'url' => config('app.url'),
                                //  'url' => 'https://www.zuopin.cloud/oauth/invotecode?userId=' . $userInfo->id,
                                'data' => [
                                    'first' => $userInfo->nickname.'你的云作品注册码已经生成，请点击详情开始注册。使用中，无论你有任何问题或建议，都可以通过使用帮助告诉我们，我们会重视你说的每一个字',
                                    'keyword1' => $userInfo->nickname,
                                    'keyword2' => $userInfo->phoneNumber,
                                    'keyword3' => $data['code'],
                                    'keyword4' => date('Y-m-d'),
                                    'remark' => '注册码只能使用一次，在完成注册前，请勿将其外泄。',
                                ],
                            ]
                        );
                        Log::error(json_encode($tmr, JSON_UNESCAPED_UNICODE));
                        if ($tmr['errmsg'] == "ok") {
                            InvoteCode::where('id', $data['id'])->update(['is_send' => 1]);
                        }
                    }
                    if ($userInfo->purePhoneNumber != '') {
                        //发送短信
                        $third_type = config('custom.send_short_message.third_type');
                        $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
                        if ($third_type == 'ali') {
                            AliSendShortMessageServer::quickSendSms(
                                $userInfo->purePhoneNumber,
                                $TemplateCodes,
                                'register_code_generate',
                                [
                                    'name' => $userInfo->is_wx_authorize == 1 ? $userInfo->nickname : '亲',
                                    'code' => $data['code'],
                                ]
                            );
                        }
                    }
                }

                return response()->json(
                    [
                        'result' => true,
                        'msg' => $datas,
                    ]
                );
                break;
            default:
                break;
        }

        return response()->json(
            [
                'result' => true,
                'msg' => '生成成功',
            ]
        );
    }

    /**
     * 创建随机数
     * @param int $length 随机数长度
     * @return string
     */
    private function str_Rand($length)
    {
        $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890";

        return substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), $length);
    }
}
