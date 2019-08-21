<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SystemRoleRequest extends FormRequest
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
            'App\Http\Controllers\Admin\System\RoleController@store' &&
            $requestMethod == 'POST'
        ) {//添加场景
            $rules = [
                'name' => 'required|min:2|max:10|unique:system_roles,name',
                'system_node_ids' => 'required',
                'status' => 'in:1',
            ];
        } elseif ($actionName ==
            'App\Http\Controllers\Admin\System\RoleController@update' &&
            ($requestMethod == "PUT" || $requestMethod == "PATCH")
        ) {//修改场景
            $id = $this->route('role');
            $rules = [
                'name' => 'required|min:2|max:10|unique:system_roles,name,' .
                    $id,
                'system_node_ids' => 'required',
                'status' => 'in:1',
            ];
        } else {
            $rules = [];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => '名称不能为空',
            'name.min' => '名称长度最小2位',
            'name.max' => '名称长度最大10位',
            'name.unique' => '名称已存在',
            'system_node_ids.required' => '至少选择一个节点',
            'status.in' => '状态值错误',
        ];
    }
}
