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
        switch ($this->getScence()) {
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
            case 'savePhotographerInfo':
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
            case 'savePhotographerInfo':
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
            'mp_login' => ['GET|App\Http\Controllers\Api\LoginController@mpLogin'],
            'login' => ['POST|App\Http\Controllers\Api\LoginController@login'],
            'save_info' => ['POST|App\Http\Controllers\Api\MyController@saveInfo'],
            'photographerWorks' => ['GET|App\Http\Controllers\Api\MyController@photographerWorks'],
            'photographerWork' => ['GET|App\Http\Controllers\Api\MyController@photographerWork'],
            'savePhotographerInfo' => ['POST|App\Http\Controllers\Api\MyController@savePhotographerInfo'],
            'savePhotographerAvatar' => ['POST|App\Http\Controllers\Api\MyController@savePhotographerAvatar'],
            'savePhotographerBgImg' => ['POST|App\Http\Controllers\Api\MyController@savePhotographerBgImg'],
        ];
    }
}
