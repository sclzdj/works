<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class TargetUserRequest extends BaseRequest
{

    public $items = [
        0 => '未处理',
        1 => '已驳回',
        2 => '已通过',
        3 => '已发送',
        4 => '已创建'
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
//            'status' => ['required', Rule::in(array_keys($this->items))],
            'invote_code_id' => 'integer',
            'source' => ['required', Rule::in([0, 1])],
            'wechat' => ['max:255'],
            'address' => 'max:255',
            'phone_code' => ['max:10', 'alpha_num'],
            'works_info' => 'array',
            'reason' => ['max:255'],
            'rank_id' => 'integer',
            'last_name' => 'max:20',
            'user_id' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'photographer_work_id.required' => '用户项目id必须传递',
            'photographer_work_id.integer' => '用户项目id必须为数字',
            'operate_type.required' => '操作方式必须传递',
            'phone_code.max' => '验证码最大10位',
            'phone_code.alpha_num' => '验证码只能是字母和数字',
            'works_info.array' => '作品信息必须是一个数组',
            'reason.max' => '理由最大不超过255',
            'rank_id.integer' => '头衔必须是一个id',
            'last_name.max' => '用户姓名没有超过20字的',
            'user_id.required' => 'user_id必须传入',
        ];
    }

}
