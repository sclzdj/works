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
            case 'baiduOauthStore':
                $rules = [
                    'user_id' => 'required|integer|exists:users,id',
                    'access_token' => 'required',
                    'expires_in' => 'required|integer',
                ];
                break;
            case 'qiniuFetchBaiduPan':
                $rules = [
                    'photographer_work_id' => 'integer',
                    'fsids' => 'required|array|min:1|max:18',
                    'is_register_photographer' => 'integer|in:0,1',
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
            case 'baiduOauthStore':
                $messages = [
                    'user_id.required' => '用户id必须传递',
                    'user_id.integer' => '用户id必须为数字',
                    'user_id.exists' => '用户不存在',
                    'access_token.required' => 'access_token必须传递',
                    'expires_in.required' => 'expires_in必须传递',
                    'expires_in.integer' => 'expires_in必须为数字',
                ];
                break;
            case 'qiniuFetchBaiduPan':
                $messages = [
                    'photographer_work_id.integer' => 'photographer_work_id必须为数字',
                    'fsids.required' => '网盘文件id不能为空',
                    'fsids.array' => '网盘文件id必须是数组',
                    'fsids.min' => '网盘文件id至少1个',
                    'fsids.max' => '网盘文件id至多18个',
                    'is_register_photographer.integer' => 'is_register_photographer必须为数字',
                    'is_register_photographer.in' => 'is_register_photographer错误',
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
            'baiduOauthStore' => ['POST|App\Http\Controllers\Api\SystemController@baiduOauthStore'],
            'qiniuFetchBaiduPan' => ['POST|App\Http\Controllers\Api\BaiduController@qiniuFetchPan'],
        ];
    }
}
