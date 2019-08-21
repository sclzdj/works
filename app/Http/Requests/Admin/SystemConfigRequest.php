<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SystemConfigRequest extends FormRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        $actionName = request()->route()->getActionName();
        $requestMethod = $this->method();
        if ($actionName ==
          'App\Http\Controllers\Admin\System\IndexController@config'
          && $requestMethod == 'POST'
        ) {//修改配置场景
            $type = $this->type ?:
              'basic';
            if ($type == 'basic') {
                $rules = [
                  'basic_admin_run' => 'in:1',
                  'basic_wxapp_run' => 'in:1',
                  'basic_page_size' => 'nullable|numeric|min:1|max:1000',
                ];
            } elseif ($type == 'admin') {
                $rules = [
                  'admin_name'          => 'nullable|max:20',
                  'admin_login_captcha' => 'in:1',
                  'admin_keywords'      => 'nullable|max:100',
                  'admin_describe'      => 'nullable|max:1000',
                  'admin_icp'           => 'nullable|max:100',
                  'admin_copyright'     => 'nullable|max:100',
                ];
            } elseif ($type == 'upload') {
                $rules = [
                  'upload_file_limit_size'          => 'nullable|numeric',
                  'upload_image_limit_size'         => 'nullable|numeric',
                  'upload_image_watermark_on'       => 'in:1',
                  'upload_image_watermark_position' => 'nullable|in:top-left,top-right,bottom-left,bottom-right,top,bottom,left,right,center',
                  'upload_image_thumb_on'           => 'in:1',
                  'upload_image_thumb_size'         => 'nullable|regex:/^[1-9]\d*\*[1-9]\d*$/',
                ];
            } else {
                $rules = [];
            }
        } elseif ($actionName ==
          'App\Http\Controllers\Admin\System\IndexController@config'
          && $requestMethod == 'PUT'
        ) {//侧栏修改场景
            $rules = [
              'name'  => 'required',
              'value' => 'required|in:0,1',
            ];
        } else {
            $rules = [];
        }

        return $rules;
    }

    public function messages() {
        return [
          'basic_admin_run.in'      => '后台开关值错误',
          'basic_wxapp_run.in'      => '小程序开关值错误',
          'basic_page_size.numeric' => '分页数量必须是个数值',
          'basic_page_size.min'     => '分页数量最小为1',
          'basic_page_size.max'     => '分页数量最大为1000',

          'admin_name.max'         => '后台名称最大20位',
          'admin_login_captcha.in' => '后台验证码值错误',
          'admin_keywords.max'     => '后台关键词最大100位',
          'admin_describe.max'     => '后台描述最大1000位',
          'admin_icp.max'          => '后台备案号最大100位',
          'admin_copyright.max'    => '后台版权信息最大100位',

          'upload_file_limit_size.numeric'  => '上传文件限制大小必须是个数值',
          'upload_image_limit_size.numeric' => '上传图片限制大小必须是个数值',
          'upload_image_watermark_on.in' => '水印开关值错误',
          'upload_image_watermark_position.in'  => '水印位置值错误',
          'upload_image_thumb_on.in' => '缩略图开关值错误',
          'upload_image_thumb_size.regex'  => '缩略图尺寸格式不正确',


          'name.required'  => '配置标识不能为空',
          'value.required' => '配置值不能为空',
          'value.in'       => '配置值错误',
        ];
    }
}
