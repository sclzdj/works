<?php
namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class StaffRequest extends BaseRequest
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
            case 'Notice':
                $rules = [
                    'user_id' => 'required',
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
            case 'Notice':
                $messages = [
                    'user_id.required' => '用户id不能为空',
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
            'Notice' => ['POST|App\Http\Controllers\Admin\Api\StaffController@Notice'],
        ];
    }
}
