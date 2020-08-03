<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PhotographerWorkRequest extends FormRequest
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
            'App\Http\Controllers\Admin\Works\PhotographerWorkController@store' &&
            $requestMethod == 'POST'
        ) {//添加场景
            $rules = [
                'customer_name' => 'required|max:50',
                'photographer_work_customer_industry_id' => 'required|exists:photographer_work_customer_industries,id',
                'project_amount' => 'required|integer|min:0',
                'hide_project_amount' => 'required|in:0,1',
                'sheets_number' => 'required|integer|min:0',
                'hide_sheets_number' => 'required|in:0,1',
                'shooting_duration' => 'required|integer|min:0',
                'hide_shooting_duration' => 'required|in:0,1',
                'photographer_work_category_id' => 'required|exists:photographer_work_categories,id',
                'sources' => 'required',
            ];
        } elseif ($actionName ==
            'App\Http\Controllers\Admin\Works\PhotographerWorkController@update' &&
            ($requestMethod == "PUT" || $requestMethod == "PATCH")
        ) {//修改场景
            $rules = [
                'customer_name' => 'required|max:50',
                'photographer_work_customer_industry_id' => 'required|exists:photographer_work_customer_industries,id',
                'project_amount' => 'required|integer|min:0',
                'hide_project_amount' => 'required|in:0,1',
                'sheets_number' => 'required|integer|min:0',
                'hide_sheets_number' => 'required|in:0,1',
                'shooting_duration' => 'required|integer|min:0',
                'hide_shooting_duration' => 'required|in:0,1',
                'photographer_work_category_id' => 'required|exists:photographer_work_categories,id',
                'sources' => 'required',
            ];
        } else {
            $rules = [];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'customer_name.required' => '客户名称不能为空',
            'customer_name.max' => '客户名称长度最大为50',
            'photographer_work_customer_industry_id.required' => '客户行业不能为空',
            'photographer_work_customer_industry_id.exists' => '客户行业不存在',
            'project_amount.required' => '项目金额不能为空',
            'project_amount.integer' => '项目金额必须为整数',
            'project_amount.min' => '项目金额最小为0元',
            'hide_project_amount.required' => '隐藏项目金额必须传递',
            'hide_project_amount.in' => '隐藏项目金额传递错误',
            'sheets_number.required' => '成片张数不能为空',
            'sheets_number.integer' => '成片张数必须为整数',
            'sheets_number.min' => '成片张数最少为1张',
            'hide_sheets_number.required' => '隐藏成片张数必须传递',
            'hide_sheets_number.in' => '隐藏成片张数传递错误',
            'shooting_duration.required' => '拍摄时长不能为空',
            'shooting_duration.integer' => '拍摄时长必须为整数',
            'shooting_duration.min' => '拍摄时长最小为1小时',
            'hide_shooting_duration.required' => '隐藏拍摄时长必须传递',
            'hide_shooting_duration.in' => '隐藏拍摄时长传递错误',
            'photographer_work_category_id.required' => '领域不能为空',
            'photographer_work_category_id.exists' => '领域不存在',
            'sources.required' => '资源不能为空',
        ];
    }
}
