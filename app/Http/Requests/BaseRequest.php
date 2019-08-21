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
                'limit' => 'numeric|min:1|max:5'
            ],
            'messages' => [
                'limit.numeric' => '条数必须传数字',
                'limit.min' => '条数最小为1',
                'limit.max' => '条数最大为5',
            ]
        ],
        'paginate' => [
            'rules' => [
                'page' => 'numeric|min:1',
                'pageSize' => 'numeric|min:1',
            ],
            'messages' => [
                'page.numeric' => '页码必须传数字',
                'page.min' => '页码最小为1',
                'pageSize.numeric' => '每页条数必须传数字',
                'pageSize.min' => '每页条数最小为1',
            ]
        ]
    ];
    protected $limit = [
        'rules' => ['limit' => 'numeric|min:1|max:5'],
        'messages' => [
            'limit.numeric' => '条数必须传数字',
            'limit.min' => '条数最小为1',
            'limit.max' => '条数最大为5',
        ]
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];
        switch ($this->getScence()) {
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
        switch ($this->getScence()) {
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
    protected function scences()
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
    protected function getScence()
    {
        $uses = request()->route()->action['uses'];
        $requestMethod = $this->method();
        $scences = $this->scences();
        $is = false;
        $scence = '';
        foreach ($scences as $k => $v) {
            if (in_array($requestMethod . '|' . $uses, $v)) {
                $is = true;
                $scence = $k;
                break;
            }
        }
        if (!$is) {
            foreach ($scences as $k => $v) {
                if (in_array($uses, $v)) {
                    $scence = $k;
                    break;
                }
            }
        }

        return $scence;
    }
}
