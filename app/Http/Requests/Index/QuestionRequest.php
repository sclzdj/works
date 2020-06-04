<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class QuestionRequest extends BaseRequest
{

    public $items = [
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
    ];
    /**
     * 获取应用到请求的验证规则
     *
     * @return array
     *
     */
    public function rules()
    {
        return [
            'status' => ['required', Rule::in([0, 1, 2])],
            'content' => 'required|max:255',
            'type' => ['required', Rule::in([1, 2])],
            'page' => ['required','max:255' , Rule::in($this->items)],
            'validated' => 'max:1024',
        ];
    }

}
