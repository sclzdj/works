<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HelpNoteRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $actionName = request()->route()->getActionName();
        $requestMethod = $this->method();
        if ($actionName ==
            'App\Http\Controllers\Admin\Works\HelpNoteController@store' &&
            $requestMethod == 'POST'
        ) {//添加场景
            $rules = [
                'title' => 'required',
                'content' => 'required',
            ];
        } elseif ($actionName ==
            'App\Http\Controllers\Admin\Works\HelpNoteController@update' &&
            ($requestMethod == "PUT" || $requestMethod == "PATCH")
        ) {//修改场景
            $rules = [
                'title' => 'required',
                'content' => 'required',
            ];
        } else {
            $rules = [];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => '帮助标题不能为空',
            'content.required' => '帮助内容不能为空',
        ];
    }
}
