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
            case 'addfamoususers':
                $rules = [
                    'photographer_id' => 'required|integer|exists:photographers,id',
                    'famous_rank_id' => 'required|integer|exists:photographer_rank_id,id',
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
            case 'addfamoususers':
                $messages = [
                    'photographer_id.required' => '用户id不能为空',
                    'famous_rank_id.integer' => '领域ID不能为空',
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
            'addfamoususers' => ['POST|App\Http\Controllers\Admin\Api\StaffController@addfamoususers'],
        ];
    }
}
