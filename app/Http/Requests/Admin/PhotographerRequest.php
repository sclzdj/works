<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PhotographerRequest extends FormRequest
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
            'App\Http\Controllers\Admin\Works\PhotographerController@store' &&
            $requestMethod == 'POST'
        ) {//添加场景
            $rules = [
                'is_tmp' => 'required',
            ];
        } elseif ($actionName ==
            'App\Http\Controllers\Admin\Works\PhotographerController@update' &&
            ($requestMethod == "PUT" || $requestMethod == "PATCH")
        ) {//修改场景
            $rules = [
                'name' => 'required|max:10',
                'gender' => 'integer|in:0,1,2',
                'province' => 'required|integer|exists:system_areas,id',
                'city' => 'required|integer|exists:system_areas,id',
                'area' => 'required|integer|exists:system_areas,id',
                'photographer_rank_id' => 'required|exists:photographer_ranks,id',
                'wechat' => 'required|max:50',
                'mobile' => 'required|regex:/^1\d{10}$/',
            ];
        } else {
            $rules = [];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => '用户名称不能为空',
            'name.max' => '用户名称长度最大为10',
            'gender.integer' => '用户性别必须为数字',
            'gender.in' => '用户性别错误',
            'province.required' => '用户所在省份必须传递',
            'province.integer' => '用户所在省份必须为数字',
            'province.exists' => '用户所在省份不存在',
            'city.required' => '用户所在城市必须传递',
            'city.integer' => '用户所在城市必须为数字',
            'city.exists' => '用户所在城市不存在',
            'area.required' => '用户所在地方必须传递',
            'area.integer' => '用户所在地方必须为数字',
            'area.exists' => '用户所在地方不存在',
            'photographer_rank_id.required' => '用户头衔不能为空',
            'photographer_rank_id.exists' => '用户头衔不存在',
            'wechat.required' => '用户微信号不能为空',
            'wechat.max' => '用户微信号长度最大为50',
            'mobile.required' => '用户手机号不能为空',
            'mobile.regex' => '用户手机号格式错误',
        ];
    }
}
