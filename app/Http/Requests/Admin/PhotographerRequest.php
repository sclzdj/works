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
                'province' => 'required|integer',
                'city' => 'required|integer',
                'area' => 'required|integer',
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
            'name.required' => '摄影师名称不能为空',
            'name.max' => '摄影师名称长度最大为10',
            'province.required' => '摄影师所在省份必须传递',
            'province.integer' => '摄影师所在省份必须为数字',
            'city.required' => '摄影师所在城市必须传递',
            'city.integer' => '摄影师所在城市必须为数字',
            'area.required' => '摄影师所在地方必须传递',
            'area.integer' => '摄影师所在地方必须为数字',
            'photographer_rank_id.required' => '摄影师头衔不能为空',
            'photographer_rank_id.exists' => '摄影师头衔不存在',
            'wechat.required' => '摄影师微信号不能为空',
            'wechat.max' => '摄影师微信号长度最大为50',
            'mobile.required' => '摄影师手机号不能为空',
            'mobile.regex' => '摄影师手机号格式错误',
        ];
    }
}
