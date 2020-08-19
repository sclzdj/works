<?php
namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;

class DeliverRequest extends BaseRequest
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
            case 'down':
                $rules = [
                    'photographer_work_id' => 'integer|exists:photographer_works,id',
                    'photographer_work_source_id' => 'integer|exists:photographer_work_sources,id',
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
            case 'down':
                $messages = [
                    'photographer_work_id.exists' => '项目不存在',
                    'photographer_work_source_id.exists' => '作品不存在',
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
            'down' => ['POST|App\Http\Controllers\Api\DeliverController@downXacodeFile'],
        ];
    }
}
