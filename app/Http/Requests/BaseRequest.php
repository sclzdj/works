<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 预定义规则
     *
     * @var array
     */
    protected $predefined = [
        'limit' => [
            'rules' => [
                'limit' => 'integer|min:1',
            ],
            'messages' => [
                'limit.integer' => '数据条数必须传整数',
                'limit.min' => '数据条数最小为1',
            ],
        ],
        'paginate' => [
            'rules' => [
                'page' => 'integer|min:1',
                'pageSize' => 'integer|min:1',
            ],
            'messages' => [
                'page.integer' => '页码必须传整数',
                'page.min' => '页码最小为1',
                'pageSize.integer' => '每页条数必须传整数',
                'pageSize.min' => '每页条数最小为1',
            ],
        ],
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];
        switch ($this->getScene()) {
            case 'limit':
                $rules = $this->predefined['limit']['rules'];
                break;
            case 'paginate':
                $rules = $this->predefined['paginate']['rules'];
                break;
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [];
        switch ($this->getScene()) {
            case 'limit':
                $messages = $this->predefined['limit']['messages'];
                break;
            case 'paginate':
                $messages = $this->predefined['paginate']['messages'];
                break;
        }

        return $messages;
    }

    /**
     * 场景配置
     *
     * @return array
     */
    protected function scenes()
    {
        return [
            'limit' => [],
            'paginate' => [],
        ];
    }

    /**
     * 获取当前场景
     *
     * @return int|string
     */
    protected function getScene()
    {
        $uses = request()->route()->action['uses'];
        $requestMethod = $this->method();
        $scenes = $this->scenes();
        $is = false;
        $scene = '';
        foreach ($scenes as $k => $v) {
            if (in_array($requestMethod.'|'.$uses, $v)) {
                $is = true;
                $scene = $k;
                break;
            }
        }
        if (!$is) {
            foreach ($scenes as $k => $v) {
                if (in_array($uses, $v)) {
                    $scene = $k;
                    break;
                }
            }
        }

        return $scene;
    }
}
