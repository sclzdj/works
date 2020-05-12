<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class QuestionRequest extends BaseRequest
{
    /**
     * 获取应用到请求的验证规则
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => ['required', Rule::in([0, 1, 2])],
            'content' => 'required|max:255',
            'type' => ['required', Rule::in([1, 2])],
            'page' => 'required|max:255',
        ];
    }

}
