<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;

class SystemRequest extends BaseRequest
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
            case 'sendSmsCode':
                $rules = [
                    'mobile' => 'required|regex:/^1\d{10}$/',
                    'purpose' => 'required',
                ];
                break;
            case 'getHelpNotes':
                $rules = $this->predefined['limit']['rules'];
                break;
            case 'getCitys':
                $rules = [
                    'province_id' => 'required|integer',
                ];
                break;
            case 'getAreas':
                $rules = [
                    'city_id' => 'required|integer',
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
            case 'sendSmsCode':
                $messages = [
                    'mobile.required' => '手机号不能为空',
                    'mobile.regex' => '手机号格式错误',
                    'purpose.required' => '用途必须传递',
                ];
                break;
            case 'getHelpNotes':
                $messages = $this->predefined['limit']['messages'];
                break;
            case 'getCitys':
                $messages = [
                    'province_id.required' => '省份id必须传递',
                    'province_id.integer' => '省份id必须为数字',
                ];
                break;
            case 'getAreas':
                $messages = [
                    'city_id.required' => '城市id必须传递',
                    'city_id.integer' => '城市id必须为数字',
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
            'sendSmsCode' => ['POST|App\Http\Controllers\Api\SystemController@sendSmsCode'],
            'getHelpNotes' => ['GET|App\Http\Controllers\Api\SystemController@getHelpNotes'],
            'getCitys' => ['GET|App\Http\Controllers\Api\SystemController@getCitys'],
            'getAreas' => ['GET|App\Http\Controllers\Api\SystemController@getAreas'],
        ];
    }
}
