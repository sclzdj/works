<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;

class PhotographerRequest extends BaseRequest
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
            case 'registerPhotographerWorkImgStore':
                $rules = [
                    'img_urls' => 'required|array|between:5,20',
                ];
                break;
            case 'registerPhotographerWorkStore':
                $rules = [
                    'customer_name' => 'required|max:50',
                    'customer_industry' => 'required|max:100',
                    'project_amount' => 'required|integer|min:1',
                    'hide_project_amount' => 'required|in:0,1',
                    'sheets_number' => 'required|integer|min:1',
                    'hide_sheets_number' => 'required|in:0,1',
                    'shooting_duration' => 'required|integer|min:1',
                    'hide_shooting_duration' => 'required|in:0,1',
                    'category' => 'required|max:100',
                    'tags' => 'array',
                ];
                break;
            case 'registerPhotographerStore':
                $rules = [
                    'name' => 'required|max:10',
                    'province' => 'required|integer',
                    'city' => 'required|integer',
                    'area' => 'required|integer',
                    'rank' => 'required|max:50',
                    'wechat' => 'required|max:50',
                    'mobile' => 'required|regex:/^1\d{10}$/',
                    'sms_code' => 'required',
                ];
                break;
            case 'photographerInfo':
                $rules = [
                    'photographer_id' => 'required|integer',
                ];
                break;
            case 'photographerWorks':
                $rules = [
                    'photographer_id' => 'required|integer',
                ];
                $rules = array_merge($rules,$this->predefined['paginate']['rules']);
                break;
            case 'photographerWork':
                $rules = [
                    'photographer_work_id' => 'required|integer',
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
            case 'registerPhotographerWorkImgStore':
                $messages = [
                    'img_urls.required' => '图片不能为空',
                    'img_urls.array' => '图片集合必须是数组',
                    'img_urls.between' => '图片至少5张，至多20张',
                ];
                break;
            case 'registerPhotographerWorkStore':
                $messages = [
                    'customer_name.required' => '客户名称不能为空',
                    'customer_name.max' => '客户名称长度最大为50',
                    'customer_industry.required' => '客户行业不能为空',
                    'customer_industry.max' => '客户行业长度最大为100',
                    'project_amount.required' => '项目金额不能为空',
                    'project_amount.integer' => '项目金额必须为整数',
                    'project_amount.min' => '项目金额最小为1元',
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
                    'category.required' => '分类不能为空',
                    'tags.array' => '标签必须是数组',
                ];
                break;
            case 'registerPhotographerStore':
                $messages = [
                    'name.required' => '摄影师名称不能为空',
                    'name.max' => '摄影师名称长度最大为10',
                    'province.required' => '摄影师所在省份必须传递',
                    'province.integer' => '摄影师所在省份必须为数字',
                    'city.required' => '摄影师所在城市必须传递',
                    'city.integer' => '摄影师所在城市必须为数字',
                    'area.required' => '摄影师所在地方必须传递',
                    'area.integer' => '摄影师所在地方必须为数字',
                    'rank.required' => '摄影师头衔不能为空',
                    'rank.max' => '摄影师头衔长度最大为50',
                    'wechat.required' => '摄影师微信号不能为空',
                    'wechat.max' => '摄影师微信号长度最大为50',
                    'mobile.required' => '摄影师手机号不能为空',
                    'mobile.regex' => '摄影师手机号格式错误',
                    'sms_code.required' => '短信验证码不能为空',
                ];
                break;
            case 'photographerInfo':
                $messages = [
                    'photographer_id.required' => '摄影师id必须传递',
                    'photographer_id.integer' => '摄影师id必须为数字',
                ];
                break;
            case 'photographerWorks':
                $messages = [
                    'photographer_id.required' => '摄影师id必须传递',
                    'photographer_id.integer' => '摄影师id必须为数字',
                ];
                $messages = array_merge($messages,$this->predefined['paginate']['messages']);
                break;
            case 'photographerWork':
                $messages = [
                    'photographer_work_id.required' => '摄影师作品集id必须传递',
                    'photographer_work_id.integer' => '摄影师作品集id必须为数字',
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
            'registerPhotographerWorkImgStore' => ['POST|App\Http\Controllers\Api\DraftController@registerPhotographerWorkImgStore'],
            'registerPhotographerWorkStore' => ['POST|App\Http\Controllers\Api\DraftController@registerPhotographerWorkStore'],
            'registerPhotographerStore' => ['POST|App\Http\Controllers\Api\DraftController@registerPhotographerStore'],
            'photographerInfo' => ['GET|App\Http\Controllers\Api\PhotographerController@info'],
            'photographerWorks' => ['GET|App\Http\Controllers\Api\PhotographerController@works'],
            'photographerWork' => ['GET|App\Http\Controllers\Api\PhotographerController@work'],
        ];
    }
}
