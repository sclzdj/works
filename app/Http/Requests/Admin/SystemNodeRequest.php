<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SystemNodeRequest extends FormRequest
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
            'App\Http\Controllers\Admin\System\NodeController@store' &&
            $requestMethod == 'POST'
        ) {//添加场景
            $rules = [
                'pid' => 'nullable|numeric|exists:system_nodes,id',
                'name' => 'required|min:2|max:10|unique:system_nodes,name',
//                'action' => 'required|min:3|max:100|regex:/^([a-zA-Z0-9\\\\]+)@([a-zA-Z0-9]+)$/',
                'status' => 'in:1',
                'sort' => 'nullable|numeric',
            ];
        } elseif ($actionName ==
            'App\Http\Controllers\Admin\System\NodeController@update' &&
            ($requestMethod == "PUT" || $requestMethod == "PATCH")
        ) {//修改场景
            $id = $this->route('node');
            $rules = [
                'pid' => 'nullable|numeric|exists:system_nodes,id',
                'name' => 'required|min:2|max:10|unique:system_nodes,name,' .
                    $id,
//                'action' => 'required|min:3|max:100|regex:/^([a-zA-Z0-9\\\\]+)@([a-zA-Z0-9]+)$/',
                'status' => 'in:1',
                'sort' => 'nullable|numeric',
            ];
        } else {
            $rules = [];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'pid.required' => '所属父级只能是数值',
            'pid.exists' => '所属父级不存在',
            'name.required' => '名称不能为空',
            'name.min' => '名称长度最小2位',
            'name.max' => '名称长度最大10位',
            'name.unique' => '名称已存在',
            'action.required' => '动作方法不能为空',
            'action.min' => '动作方法长度最小3位',
            'action.max' => '动作方法长度最大100位',
            'action.regex' => '动作方法不符合规范',
            'sort.numeric' => '排序只能是数值',
            'status.in' => '状态值错误',
        ];
    }
}
