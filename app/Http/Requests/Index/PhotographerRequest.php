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
        switch ($this->getScene()) {
            case 'savePhotographerWorkSourceStore':
                $rules = [
                    'sources' => 'required|array|min:1|max:18',
                    'sources.*.key' => 'required',
                    'sources.*.url' => 'required',
                    'sources.*.type' => 'required|in:image,video',
                ];
                break;
            case 'savePhotographerWorkStore':
                $rules = [
//                    'name' => 'required|max:50',
//                    'describe' => 'present|max:2000',
//                    'is_business' => 'required|in:0,1',
//                    'location' => 'required|max:100',
//                    'address' => 'required|max:2000',
//                    'latitude' => 'required|max:100',
//                    'longitude' => 'required|max:100',
                    'customer_name' => 'required|max:50',
                    'photographer_work_customer_industry_id' => 'required|exists:photographer_work_customer_industries,id',
                    'project_amount' => 'required|integer|min:0',
                    'hide_project_amount' => 'required|in:0,1',
                    'sheets_number' => 'required|integer|min:1',
                    'hide_sheets_number' => 'required|in:0,1',
                    'shooting_duration' => 'required|integer|min:1',
                    'hide_shooting_duration' => 'required|in:0,1',
                    'photographer_work_category_id' => 'required|exists:photographer_work_categories,id',
                    'tags' => 'array',
                    'tags.*' => 'required|max:50',
                ];
                break;
            case 'savePhotographerStore':
                $rules = [
                    'name' => 'required|max:10',
                    'gender' => 'integer|in:0,1,2',
                    'province' => 'required|integer|exists:system_areas,id',
                    'city' => 'required|integer|exists:system_areas,id',
                    'area' => 'required|integer|exists:system_areas,id',
                    'photographer_rank_id' => 'required|exists:photographer_ranks,id',
                    'wechat' => 'required|max:50',
                    'mobile' => 'required|regex:/^1\d{10}$/',
                    'sms_code' => 'required',
                    'auth_tags' => 'array',
                    'auth_tags.*' => 'required|max:50',
                    'award_tags' => 'array',
                    'award_tags.*' => 'required|max:50',
                    'educate_tags' => 'array',
                    'educate_tags.*' => 'required|max:50',
                    'equipment_tags' => 'array',
                    'equipment_tags.*' => 'required|max:50',
                    'social_tags' => 'array',
                    'social_tags.*' => 'required|max:50',
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
                $rules = array_merge($rules, $this->predefined['paginate']['rules']);
                break;
            case 'photographerWork':
                $rules = [
                    'photographer_work_id' => 'required|integer',
                ];
                break;
            case 'xacodeNext':
                $rules = [
                    'photographer_id' => 'integer',
                    'current_photographer_work_id' => 'required|integer',
                    'is_select_work' => 'integer|in:0,1',
                ];
                break;
            case 'photographerWorkSource':
                $rules = [
                    'photographer_work_source_id' => 'required|integer',
                ];
                break;
            case 'rankingList':
                $rules = array_merge($rules, $this->predefined['limit']['rules']);
                $rules['work_limit']='integer';
                $rules['source_limit']='integer';
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
            case 'savePhotographerWorkSourceStore':
                $messages = [
                    'sources.required' => '资源不能为空',
                    'sources.array' => '资源必须是数组',
                    'sources.min' => '资源至少1个',
                    'sources.max' => '资源至多18个',
                    'sources.*.key.required' => '资源key不能为空',
                    'sources.*.url.required' => '资源url不能为空',
                    'sources.*.type.required' => '资源类型不能为空',
                    'sources.*.type.in' => '资源类型错误',
                ];
                break;
            case 'savePhotographerWorkStore':
                $messages = [
                    'name.required' => '项目名称不能为空',
                    'name.max' => '项目名称长度最大为50',
                    'describe.present' => '项目描述必须传递',
                    'describe.max' => '项目描述长度最大为2000',
                    'is_business.required' => '是否商业项目必须传递',
                    'is_business.in' => '是否商业项目传递错误',
                    'location.required' => '地理位置名称不能为空',
                    'location.max' => '地理位置名称长度最大为100',
                    'address.required' => '详细地址不能为空',
                    'address.max' => '详细地址长度最大为2000',
                    'latitude.required' => '维度不能为空',
                    'latitude.max' => '维度长度最大为100',
                    'longitude.required' => '经度不能为空',
                    'longitude.max' => '经度长度最大为100',
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
                    'tags.array' => '标签必须是数组',
                    'tags.*.required' => '标签名称不能为空',
                    'tags.*.max' => '标签名称长度最大为50',
                ];
                break;
            case 'savePhotographerStore':
                $messages = [
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
                    'sms_code.required' => '短信验证码不能为空',
                    'auth_tags.array' => '认证情况必须是数组',
                    'auth_tags.*.required' => '认证情况标签名称不能为空',
                    'auth_tags.*.max' => '认证情况标签名称长度最大为50',
                    'award_tags.array' => '获奖情况必须是数组',
                    'award_tags.*.required' => '获奖情况标签名称不能为空',
                    'award_tags.*.max' => '获奖情况标签名称长度最大为50',
                    'educate_tags.array' => '教育情况必须是数组',
                    'educate_tags.*.required' => '教育情况标签名称不能为空',
                    'educate_tags.*.max' => '教育情况标签名称长度最大为50',
                    'equipment_tags.array' => '器材清单必须是数组',
                    'equipment_tags.*.required' => '器材清单标签名称不能为空',
                    'equipment_tags.*.max' => '器材清单标签名称长度最大为50',
                    'social_tags.array' => '社交网络必须是数组',
                    'social_tags.*.required' => '社交网络标签名称不能为空',
                    'social_tags.*.max' => '社交网络标签名称长度最大为50',
                ];
                break;
            case 'photographerInfo':
                $messages = [
                    'photographer_id.required' => '用户id必须传递',
                    'photographer_id.integer' => '用户id必须为数字',
                ];
                break;
            case 'photographerWorks':
                $messages = [
                    'photographer_id.required' => '用户id必须传递',
                    'photographer_id.integer' => '用户id必须为数字',
                ];
                $messages = array_merge($messages, $this->predefined['paginate']['messages']);
                break;
            case 'photographerWork':
                $messages = [
                    'photographer_work_id.required' => '用户项目id必须传递',
                    'photographer_work_id.integer' => '用户项目id必须为数字',
                ];
                break;
            case 'xacodeNext':
                $messages = [
                    'photographer_id.integer' => '用户id必须为数字',
                    'current_photographer_work_id.required' => '当前用户项目id必须传递',
                    'current_photographer_work_id.integer' => '当前师项目id必须为数字',
                    'is_select_work.integer' => '是否查出项目信息必须为数字',
                    'is_select_work.in' => '是否查出项目信息错误',
                ];
                break;
            case 'photographerWorkSource':
                $messages = [
                    'photographer_work_source_id.required' => '作品id必须传递',
                    'photographer_work_source_id.integer' => '作品id必须为数字',
                ];
                break;
            case 'rankingList':
                $messages = array_merge($messages, $this->predefined['limit']['messages']);
                $messages['work_limit.integer'] = '项目数量必须传整数';
                $messages['source_limit.integer'] = '作品数量必须传整数';
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
            'savePhotographerWorkSourceStore' => [
                'POST|App\Http\Controllers\Api\DraftController@registerPhotographerWorkSourceStore',
                'POST|App\Http\Controllers\Api\DraftController@addPhotographerWorkSourceStore',
            ],
            'savePhotographerWorkStore' => [
                'POST|App\Http\Controllers\Api\DraftController@registerPhotographerWorkStore',
                'POST|App\Http\Controllers\Api\DraftController@addPhotographerWorkStore',
                'POST|App\Http\Controllers\Api\DraftController@registerPhotographerWorkStore2',
            ],
            'savePhotographerStore' => [
                'POST|App\Http\Controllers\Api\DraftController@registerPhotographerStore',
                'POST|App\Http\Controllers\Api\MyController@savePhotographerInfo',
            ],
            'photographerInfo' => ['GET|App\Http\Controllers\Api\PhotographerController@info'],
            'photographerWorks' => [
                'GET|App\Http\Controllers\Api\PhotographerController@works',
                'GET|App\Http\Controllers\Api\PhotographerController@poster',
            ],
            'photographerWork' => [
                'GET|App\Http\Controllers\Api\PhotographerController@work',
                'GET|App\Http\Controllers\Api\PhotographerController@workPoster',
            ],
            'xacodeNext' => ['GET|App\Http\Controllers\Api\PhotographerController@xacodeNext'],
            'photographerWorkSource' => ['GET|App\Http\Controllers\Api\PhotographerController@workSource'],
            'rankingList' => ['GET|App\Http\Controllers\Api\PhotographerController@rankingList'],
        ];
    }
}
