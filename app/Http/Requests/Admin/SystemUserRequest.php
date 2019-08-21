<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $actionName = request()->route()->getActionName();
        $requestMethod = $this->method();
        if ($actionName ==
            'App\Http\Controllers\Admin\System\UserController@store' &&
            $requestMethod == 'POST'
        ) {//添加场景
            $rules = [
                'username' => 'required|min:2|max:10|regex:/^^[\x4e00-\x9fa5]+$/|unique:system_users,username',
                'password' => 'required|min:5|max:18|regex:/^[0-9a-zA-Z_!@#$%^&*]+$/',
                'nickname' => 'required|min:2|max:10',
                'type' => 'required|in:0,1,2',
                'system_role_ids' => 'required_if:type,1',
                'system_node_ids' => 'required_if:type,2',
                'status' => 'in:1',
            ];
        } elseif ($actionName ==
            'App\Http\Controllers\Admin\System\UserController@update' &&
            ($requestMethod == "PUT" || $requestMethod == "PATCH")
        ) {//修改场景
            $id = $this->route('user');
            $rules = [
                'username' => 'required|min:2|max:10|regex:/^^[\x4e00-\x9fa5]+$/|unique:system_users,username,' .
                    $id,
                'password' => 'nullable|min:5|max:18|regex:/^[0-9a-zA-Z_!@#$%^&*]+$/',
                'nickname' => 'required|min:2|max:10',
                'type' => 'required|in:0,1,2',
                'system_role_ids' => 'required_if:type,1',
                'system_node_ids' => 'required_if:type,2',
                'status' => 'in:1',
            ];
        } elseif ($actionName ==
            'App\Http\Controllers\Admin\System\IndexController@setInfo' &&
            $requestMethod == 'PUT'
        ) {//资料设置
            $rules = [
                'password' => 'nullable|min:5|max:18|regex:/^[0-9a-zA-Z_!@#$%^&*]+$/',
                'nickname' => 'required|min:2|max:10',
            ];
        } elseif ($actionName ==
            'App\Http\Controllers\Admin\System\IndexController@updatePassword' &&
            $requestMethod == 'PATCH'
        ) {//资料设置
            $rules = [
                'password' => 'required|min:5|max:18|regex:/^[0-9a-zA-Z_!@#$%^&*]+$/',
            ];
        } else {
            $rules = [];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'username.required' => '账号不能为空',
            'username.min' => '账号长度最小2位',
            'username.max' => '账号长度最大10位',
            'username.regex' => '账号不能含有中文或空格',
            'username.unique' => '账号已存在',
            'password.required' => '密码不能为空',
            'password.min' => '密码长度最小5位',
            'password.max' => '密码长度最大18位',
            'password.regex' => '密码含有只能包含大小写字母、数字和特殊字符：_!@#$%^&*',
            'nickname.required' => '昵称不能为空',
            'nickname.min' => '昵称长度最小2位',
            'nickname.max' => '昵称长度最大10位',
            'type.required' => '类型不能为空',
            'type.in' => '类型值错误',
            'system_role_ids.required_if' => '至少选择一位角色',
            'system_node_ids.required_if' => '至少选择一个节点',
            'status.in' => '状态值错误',
        ];
    }
}
