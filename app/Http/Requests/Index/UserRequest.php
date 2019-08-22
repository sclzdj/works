<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;

class UserRequest extends BaseRequest
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
            case 'mp_login':
                $rules = [
                    'code' => 'required',
                ];
                break;
            case 'login':
                $rules = [
                    'username' => 'required|alpha_dash',
                    'password' => 'required',
                ];
                break;
            case 'save_info':
                $rules = [
                    'nickname' => 'required',
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
            case 'mp_login':
                $messages = [
                    'code.required' => 'code必须传递',
                ];
                break;
            case 'login':
                $messages = [
                    'username.required' => '用户名必须传递',
                    'username.alpha_dash' => '用户名格式错误',
                    'password.required' => '密码必须传递',
                ];
                break;
            case 'save_info':
                $messages = [
                    'nickname.required' => '昵称必须传递',
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
            'mp_login' => ['GET|App\Http\Controllers\Api\LoginController@mpLogin'],
            'login' => ['POST|App\Http\Controllers\Api\LoginController@login'],
            'save_info' => ['POST|App\Http\Controllers\Api\MyController@saveInfo'],
        ];
    }
}
