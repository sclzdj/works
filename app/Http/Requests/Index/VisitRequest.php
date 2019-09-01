<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;

class VisitRequest extends BaseRequest
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
            case 'inRecord':
                $rules = [
                    'page_name' => 'required|in:photographer_home,photographer_work',
                    'photographer_id' => 'required|integer',
                    'photographer_work_id' => 'required_if:page_name,photographer_work|integer',
                    'in_type' => 'required|in:share_in,routine_in',
                    'shared_user_id' => 'required_if:in_type,share_in|integer',
                ];
                break;
            case 'shareRecord':
                $rules = [
                    'page_name' => 'required|in:photographer_home,photographer_work',
                    'photographer_id' => 'required|integer',
                    'photographer_work_id' => 'required_if:page_name,photographer_work|integer',
                ];
                break;
            case 'setRemind':
                $rules = [
                    'visitor_id' => 'required|integer',
                    'is_remind' => 'required|in:0,1',
                ];
                break;
            case 'setTag':
                $rules = [
                    'visitor_id' => 'required|integer',
                    'visitor_tag_id' => 'required|integer',
                ];
                break;
            case 'visitors':
                $rules = [
                    'visitor_tag_id' => 'integer',
                ];
                $rules = array_merge($rules, $this->predefined['paginate']['rules']);
                break;
            case 'visitor':
                $rules = [
                    'visitor_id' => 'required|integer',
                ];
                break;
            case 'visitorRecords':
                $rules = [
                    'visitor_id' => 'required|integer',
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
        switch ($this->getScene()) {
            case 'inRecord':
                $messages = [
                    'page_name.required' => '页面名称必须传递',
                    'page_name.in' => '页面名称错误',
                    'photographer_id.required' => '摄影师id必须传递',
                    'photographer_id.integer' => '摄影师id必须为数字',
                    'photographer_work_id.required_if' => '作品集id必须传递',
                    'photographer_work_id.integer' => '作品集id必须为数字',
                    'in_type.required' => '进入方式必须传递',
                    'in_type.in' => '进入方式错误',
                    'shared_user_id.required_if' => '分享用户id必须传递',
                    'shared_user_id.integer' => '分享用户id必须为数字',
                ];
                break;
            case 'shareRecord':
                $messages = [
                    'page_name.required' => '页面名称必须传递',
                    'page_name.in' => '页面名称错误',
                    'photographer_id.required' => '摄影师id必须传递',
                    'photographer_id.integer' => '摄影师id必须为数字',
                    'photographer_work_id.required_if' => '作品集id必须传递',
                    'photographer_work_id.integer' => '作品集id必须为数字',
                ];
                break;
            case 'setRemind':
                $messages = [
                    'visitor_id.required' => '访客id必须传递',
                    'visitor_id.integer' => '访客id必须为数字',
                    'is_remind.required' => '是否提醒必须传递',
                    'is_remind.in' => '是否提醒错误',
                ];
                break;
            case 'setTag':
                $messages = [
                    'visitor_id.required' => '访客id必须传递',
                    'visitor_id.integer' => '访客id必须为数字',
                    'visitor_tag_id.required' => '访客标签id必须传递',
                    'visitor_tag_id.integer' => '访客标签id必须为数字',
                ];
                break;
            case 'visitors':
                $messages = [
                    'visitor_tag_id.integer' => '访客标签id必须为数字',
                ];
                $messages = array_merge($messages, $this->predefined['paginate']['messages']);
                break;
            case 'visitor':
                $messages = [
                    'visitor_id.required' => '访客id必须传递',
                    'visitor_id.integer' => '访客id必须为数字',
                ];
                break;
            case 'visitorRecords':
                $messages = [
                    'visitor_id.required' => '访客id必须传递',
                    'visitor_id.integer' => '访客id必须为数字',
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
    public function scenes()
    {
        return [
            'inRecord' => ['POST|App\Http\Controllers\Api\VisitController@inRecord'],
            'shareRecord' => ['POST|App\Http\Controllers\Api\VisitController@shareRecord'],
            'setRemind' => ['POST|App\Http\Controllers\Api\VisitController@setRemind'],
            'setTag' => ['POST|App\Http\Controllers\Api\VisitController@setTag'],
            'visitors' => ['GET|App\Http\Controllers\Api\VisitController@visitors'],
            'visitor' => ['GET|App\Http\Controllers\Api\VisitController@visitor'],
            'visitorRecords' => ['GET|App\Http\Controllers\Api\VisitController@visitorRecords'],
        ];
    }
}
