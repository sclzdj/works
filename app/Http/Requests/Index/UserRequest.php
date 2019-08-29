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
        switch ($this->getScene()) {
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
            case 'photographerWorks':
                $rules = array_merge($rules, $this->predefined['paginate']['rules']);
                break;
            case 'savePhotographerAvatar':
                $rules = [];
                break;
            case 'savePhotographerBgImg':
                $rules = [];
                break;
            case 'photographerWork':
                $rules = [
                    'photographer_work_id' => 'required|integer',
                ];
                break;
            case 'savePhotographerWorkInfo':
                $rules = [
                    'photographer_work_id' => 'required|integer',
                    'sources' => 'required|array|min:1',
                    'sources.*.url' => 'required',
                    'sources.*.type' => 'required|in:image,video',
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
                    'tags.*' => 'required|max:50',
                ];
                break;
            case 'viewRecords':
                $rules = array_merge($rules, $this->predefined['paginate']['rules']);
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
        switch ($this->getScene()) {
            case 'mp_login':
                $messages = [
                    'code.required' => 'code必须传递',
                ];
                break;
            case 'login':
                $messages = [
                    'username.required' => '用户名不能为空',
                    'username.alpha_dash' => '用户名格式错误',
                    'password.required' => '密码必须传递',
                ];
                break;
            case 'save_info':
                $messages = [
                    'nickname.required' => '昵称不能为空',
                ];
                break;
            case 'photographerWorks':
                $messages = array_merge($messages, $this->predefined['paginate']['messages']);
                break;
            case 'savePhotographerAvatar':
                $messages = [];
                break;
            case 'savePhotographerBgImg':
                $messages = [];
                break;
            case 'photographerWork':
                $messages = [
                    'photographer_work_id.required' => '摄影师作品集id必须传递',
                    'photographer_work_id.integer' => '摄影师作品集id必须为数字',
                ];
                break;
            case 'savePhotographerWorkInfo':
                $messages = [
                    'photographer_work_id.required' => '摄影师作品集id必须传递',
                    'photographer_work_id.integer' => '摄影师作品集id必须为数字',
                    'sources.required' => '资源不能为空',
                    'sources.array' => '资源必须是数组',
                    'sources.min' => '资源至少1个',
                    'sources.*.url.required' => '资源url不能为空',
                    'sources.*.type.required' => '资源类型不能为空',
                    'sources.*.type.in' => '资源类型错误',
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
                    'tags.*.required' => '标签名称不能为空',
                    'tags.*.max' => '标签名称长度最大为50',
                ];
                break;
            case 'viewRecords':
                $rules = array_merge($messages, $this->predefined['paginate']['messages']);
                break;
        }

        return $messages;
    }

    /**
     * 场景配置
     *
     * @return array
     */
    public function scenes()
    {
        return [
            'mp_login' => ['GET|App\Http\Controllers\Api\LoginController@mpLogin'],
            'login' => ['POST|App\Http\Controllers\Api\LoginController@login'],
            'save_info' => ['POST|App\Http\Controllers\Api\MyController@saveInfo'],
            'photographerWorks' => ['GET|App\Http\Controllers\Api\MyController@photographerWorks'],
            'photographerWork' => ['GET|App\Http\Controllers\Api\MyController@photographerWork'],
            'savePhotographerAvatar' => ['POST|App\Http\Controllers\Api\MyController@savePhotographerAvatar'],
            'savePhotographerBgImg' => ['POST|App\Http\Controllers\Api\MyController@savePhotographerBgImg'],
            'savePhotographerWorkInfo' => ['POST|App\Http\Controllers\Api\MyController@savePhotographerWorkInfo'],
            'viewRecords' => ['GET|App\Http\Controllers\Api\MyController@viewRecords'],
        ];
    }
}
