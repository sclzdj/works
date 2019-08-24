<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;

class SystemRequest extends BaseRequest
{
    /**
     * 规则
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];
        switch ($this->getScence()) {
            case 'sendSmsCode':
                $rules = [
                    'mobile' => 'required|regex:/^1\d{10}$/',
                    'purpose' => 'required',
                ];
                break;
        }

        return $rules;
    }

    /**
     * 提示信息
     *
     * @return array
     */
    public function messages()
    {
        $messages = [];
        switch ($this->getScence()) {
            case 'sendSmsCode':
                $messages = [
                    'mobile.required' => '手机号不能为空',
                    'mobile.regex' => '手机号格式错误',
                    'purpose.required' => '用途必须传递',
                ];
                break;
        }
        return $messages;
    }

    /**
     * 场景配置
     *
     * @return array
     */
    public function scences()
    {
        return [
            'sendSmsCode' => ['POST|App\Http\Controllers\Api\SystemController@sendSmsCode'],
        ];
    }
}
