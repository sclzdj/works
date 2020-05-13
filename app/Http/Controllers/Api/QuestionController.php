<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/21
 * Time: 15:50
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\QuestionRequest;
use App\Model\Index\Question;

class QuestionController extends UserGuardController
{
    public function collect(QuestionRequest $request)
    {
        try {
            $validated = $request->validated();
            $user_id = (auth($this->guard)->user())->id;

            $qustion = new Question();
            $qustion->status = $validated['status'];
            $qustion->type = $validated['type'];
            $qustion->content = $validated['content'];
            $qustion->page = $validated['page'];
            if (isset($validated['attachment'])) {
                $qustion->attachment = json_encode($validated['attachment'], JSON_UNESCAPED_UNICODE);
            }
            $qustion->user_id = $user_id;
            $qustion->save();

            return $this->response->array([
                'status' => 200,
                'result' => '添加完成'
            ]);

        } catch (\Exception $exception) {
            return $this->response->error($exception->getMessage(), 500);
        }
    }

    public function getPage()
    {
        return $this->response->array([
            'status' => 200,
            'items' => [
                '创建-创建云作品',
                '添加-从手机相册选图',
                '添加-从百度网盘选图',
                '添加-添加/修改项目信息',
                '展示-自己的作品/项目/合集',
                '展示-别人的作品/项目/合集',
                '分享-发给微信好友',
                '分享-发给微信好友',
                '分享-生成获客海报',
                '分享-生成获客海报',
                '分享-生成小程序码',
                '人脉-访客登录'.
                '人脉-访客列表',
                '人脉-访客详情',
                '其他-最近浏览',
                '其他-人脉排行榜',
                '其他-修改个人资料',
                '其他-学习使用技巧',
                '其他-其他问题'
            ]
        ]);
    }
}
