<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;
use App\Rules\ValidateWordSecurity;
use App\Rules\ValidationName;
use Illuminate\Validation\Rule;

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
        switch ($this->getScene()) {
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
            case 'accept':
                $rules = [
                    'photographer_id' => 'required|integer|exists:photographers,id',
                    'request_photographer_id' => 'required',
                ];
                break;
            case 'save_info':
                $rules = [
                    'encryptedData' => 'required',
                    'iv' => 'required',
                ];
                break;
            case 'saveMobile':
                $rules = [
                    'encryptedData' => 'required',
                    'iv' => 'required',
                ];
                break;
            case 'photographerWorks':
                $rules = [
                    'is_roof_order_by' => 'integer|in:0,1',
                ];
                $rules = array_merge($rules, $this->predefined['paginate']['rules']);
                break;
            case 'photographerWorkSources':
                $rules = [
                    'is_roof_order_by' => 'integer|in:0,1',
                ];
                $rules = array_merge($rules, $this->predefined['paginate']['rules']);
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
            case 'setRoof':
                $rules = [
                    'photographer_work_id' => 'required|integer',
                    'operate_type' => 'required|integer|in:0,1',
                ];
                break;
            case 'savePhotographerWorkInfo':
                $rules = [
                    'photographer_work_id' => 'required|integer',
//                    'sources' => 'array',
//                    'sources.*.key' => 'required',
//                    'sources.*.url' => 'required',
//                    'sources.*.type' => 'required|in:image,video',
//                    'sources.*.sort' => 'required|integer',
//                    'fsids' => 'array',
//                    'fsids.*.fsid' => 'required',
//                    'fsids.*.sort' => 'required|integer',
//                    'name' => 'required|max:50',
//                    'describe' => 'present|max:2000',
//                    'is_business' => 'required|in:0,1',
//                    'location' => 'required|max:100',
//                    'address' => 'required|max:2000',
//                    'latitude' => 'required|max:100',
//                    'longitude' => 'required|max:100',
//                    'customer_name' => ['string', new ValidationName, new ValidateWordSecurity],
//                    'photographer_work_customer_industry_id' => 'integer|exists:photographer_work_customer_industries,id',
//                    'project_amount' => 'integer|min:0',
//                    'hide_project_amount' => 'integer|in:0,1',
//                    'sheets_number' => 'integer|min:0',
//                    'hide_sheets_number' => 'integer|in:0,1',
//                    'shooting_duration' => 'integer|min:0',
//                    'hide_shooting_duration' => 'integer|in:0,1',
//                    'photographer_work_category_id' => 'integer|exists:photographer_work_categories,id',
//                    'tags' => 'array',
//                    'tags.*' => 'max:50',
                ];
                break;
            case 'viewRecords':
                $rules = array_merge($rules, $this->predefined['paginate']['rules']);
                $rules['work_limit'] = 'integer';
                $rules['source_limit'] = 'integer';
                break;
            case 'photographerStatistics':
                $rules = [
                    'rankListLast' => 'integer|min:1',
                ];
                break;
            case 'saveDocPdf':
                $rules = [
                    'name' => 'required|max:100',
                    'photographer_work_ids' => 'array',
                    'photographer_work_ids.*' => 'required|integer',
                ];
                break;
            case 'getDocPdfStatus':
                $rules = [
                    'doc_pdf_id' => 'required|integer',
                ];
                break;
            case 'docPdfs':
                $rules = array_merge($rules, $this->predefined['paginate']['rules']);
                break;
            case 'photographerWorkHide':
                $rules = [
                    'photographer_work_id' => 'required|integer',
                    'type' => ['required', 'integer', Rule::in([0, 1, 2]),],
                    'status' => ['required', 'integer', Rule::in([0, 1]),],
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
            case 'mp_login':
                $messages = [
                    'code.required' => 'code必须传递',
                ];
                break;
            case 'login':
                $messages = [
                    'username.required' => '微信用户名不能为空',
                    'username.alpha_dash' => '微信用户名格式错误',
                    'password.required' => '密码必须传递',
                ];
                break;
            case 'accept':
                $messages = [
                    'photographer_id.required' => '摄影师id不能为空',
                    'photographer_id.exists' => '摄影师不存在',
                    'request_photographer_id.required' => '受邀者用户id不能为空',
                ];
                break;
            case 'save_info':
                $messages = [
                    'encryptedData.required' => 'encryptedData不能为空',
                    'iv.required' => 'iv不能为空',
                ];
                break;
            case 'saveMobile':
                $messages = [
                    'encryptedData.required' => 'encryptedData不能为空',
                    'iv.required' => 'iv不能为空',
                ];
                break;
            case 'photographerWorks':
                $messages = [
                    'is_roof_order_by.integer' => '是否置顶排序',
                    'is_roof_order_by.in' => '是否置顶排序错误',
                ];
                $messages = array_merge($messages, $this->predefined['paginate']['messages']);
                break;
            case 'photographerWorkSources':
                $messages = [
                    'is_roof_order_by.integer' => '是否置顶排序',
                    'is_roof_order_by.in' => '是否置顶排序错误',
                ];
                $messages = array_merge($messages, $this->predefined['paginate']['messages']);
                break;
            case 'savePhotographerAvatar':
                $messages = [];
                break;
            case 'savePhotographerBgImg':
                $messages = [];
                break;
            case 'photographerWork':
                $messages = [
                    'photographer_work_id.required' => '用户项目id必须传递',
                    'photographer_work_id.integer' => '用户项目id必须为数字',
                ];
                break;
            case 'setRoof':
                $messages = [
                    'photographer_work_id.required' => '用户项目id必须传递',
                    'photographer_work_id.integer' => '用户项目id必须为数字',
                    'operate_type.required' => '操作方式必须传递',
                    'operate_type.integer' => '操作方式必须为数字',
                    'operate_type.in' => '操作方式错误',
                ];
                break;
            case 'savePhotographerWorkInfo':
                $messages = [
                    'photographer_work_id.required' => '用户项目id必须传递',
                    'photographer_work_id.integer' => '用户项目id必须为数字',
                    'sources.array' => '资源必须是数组',
                    'sources.*.key.required' => '资源key不能为空',
                    'sources.*.url.required' => '资源url不能为空',
                    'sources.*.type.required' => '资源类型不能为空',
                    'sources.*.type.in' => '资源类型错误',
                    'sources.*.sort.required' => '资源排序不能为空',
                    'sources.*.sort.integer' => '资源排序必须为数字',
                    'fsids.array' => '网盘文件集合必须是数组',
                    'fsids.*.fsid.required' => '网盘文件id不能为空',
                    'fsids.*.sort.required' => '网盘文件id排序不能为空',
                    'fsids.*.sort.integer' => '网盘文件id排序必须为数字',
                    'name.required' => '项目名称不能为空',
                    'name.max' => '项目名称长度最大为50',
                    'describe.present' => '项目描述必须传递',
                    'describe.max' => '项目描述长度最大为2000',
                    'is_business.required' => '是否商业项目必须传递',
                    'is_business.in' => '是否商业项目传递错误',
                    'location.required' => '地理位置名称不能为空',
                    'location.max' => '地理位置名称长度最大为100',
                    'address.required' => '详细地址不能为空',
                    'address.max' => '详细地址长度最大为2000',
                    'latitude.required' => '维度不能为空',
                    'latitude.max' => '维度长度最大为100',
                    'longitude.required' => '经度不能为空',
                    'longitude.max' => '经度长度最大为100',
                    'customer_name.required' => '客户名称不能为空',
                    'customer_name.max' => '客户名称长度最大为50',
                    'photographer_work_customer_industry_id.required' => '客户行业不能为空',
                    'photographer_work_customer_industry_id.exists' => '客户行业不存在',
                    'project_amount.required' => '项目金额不能为空',
                    'project_amount.integer' => '项目金额必须为整数',
                    'project_amount.min' => '项目金额最小为0元',
                    'hide_project_amount.required' => '隐藏项目金额必须传递',
                    'hide_project_amount.in' => '隐藏项目金额传递错误',
                    'sheets_number.required' => '成片张数不能为空',
                    'sheets_number.integer' => '成片张数必须为整数',
                    'sheets_number.min' => '成片张数最少为1张',
                    'hide_sheets_number.required' => '隐藏成片张数必须传递',
                    'hide_sheets_number.in' => '隐藏成片张数传递错误',
                    'shooting_duration.required' => '拍摄时长不能为空',
                    'shooting_duration.integer' => '拍摄时长必须为整数',
                    'shooting_duration.min' => '拍摄时长最小为1小时',
                    'hide_shooting_duration.required' => '隐藏拍摄时长必须传递',
                    'hide_shooting_duration.in' => '隐藏拍摄时长传递错误',
                    'photographer_work_category_id.required' => '领域不能为空',
                    'photographer_work_category_id.exists' => '领域不存在',
                    'tags.array' => '标签必须是数组',
                    'tags.*.required' => '标签名称不能为空',
                    'tags.*.max' => '标签名称长度最大为50',
                ];
                break;
            case 'viewRecords':
                $messages = array_merge($messages, $this->predefined['paginate']['messages']);
                $messages['work_limit.integer'] = '项目数量必须传整数';
                $messages['source_limit.integer'] = '作品数量必须传整数';
                break;
            case 'photographerStatistics':
                $messages = [
                    'rankListLast.integer' => '人脉排行榜最后一名必须传整数',
                    'rankListLast.min' => '人脉排行榜最后一名最小为1',
                ];
                break;
            case 'saveDocPdf':
                $messages = [
                    'name.required' => 'PDF名称必须传递',
                    'name.max' => 'PDF名称长度最大为100',
                    'photographer_work_ids.array' => 'PDF的项目id必须是数组',
                    'photographer_work_ids.*.required' => 'PDF的项目id不能为空',
                    'photographer_work_ids.*.integer' => 'PDF的项目id必须为数字',
                ];
                break;
            case 'getDocPdfStatus':
                $messages = [
                    'doc_pdf_id.required' => 'PDFid必须传递',
                    'doc_pdf_id.integer' => 'PDFid必须为数字',
                ];
                break;
            case 'docPdfs':
                $messages = array_merge($messages, $this->predefined['paginate']['messages']);
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
            'mp_login' => ['GET|App\Http\Controllers\Api\LoginController@mpLogin'],
            'login' => ['POST|App\Http\Controllers\Api\LoginController@login'],
            'accept' => ['POST|App\Http\Controllers\Api\InviteController@accept'],
            'save_info' => ['POST|App\Http\Controllers\Api\MyController@saveInfo'],
            'saveMobile' => ['POST|App\Http\Controllers\Api\MyController@saveMobile'],
            'photographerWorks' => ['GET|App\Http\Controllers\Api\MyController@photographerWorks'],
            'photographerWorkHide' => ['GET|App\Http\Controllers\Api\MyController@photographerWorkHide'],
            'photographerWorkSources' => [
                'GET|App\Http\Controllers\Api\MyController@photographerWorkSources',
                'GET|App\Http\Controllers\Api\MyController@photographerWorkSourcesSimple',
            ],
            'photographerWork' => [
                'GET|App\Http\Controllers\Api\MyController@photographerWork',
                'DELETE|App\Http\Controllers\Api\MyController@photographerWorkDelete',
            ],
            'setRoof' => ['GET|App\Http\Controllers\Api\MyController@setRoof'],
            'savePhotographerAvatar' => ['POST|App\Http\Controllers\Api\MyController@savePhotographerAvatar'],
            'savePhotographerBgImg' => ['POST|App\Http\Controllers\Api\MyController@savePhotographerBgImg'],
            'savePhotographerWorkInfo' => ['POST|App\Http\Controllers\Api\MyController@savePhotographerWorkInfo'],
            'viewRecords' => ['GET|App\Http\Controllers\Api\MyController@viewRecords'],
            'saveDocPdf' => ['POST|App\Http\Controllers\Api\MyController@saveDocPdf'],
            'docPdfs' => ['GET|App\Http\Controllers\Api\MyController@docPdfs'],
            'getDocPdfStatus' => [
                'GET|App\Http\Controllers\Api\MyController@getDocPdfStatus',
                'DELETE|App\Http\Controllers\Api\MyController@docPdfDelete',
            ],
            'photographerStatistics' => ['GET|App\Http\Controllers\Api\MyController@photographerStatistics'],
        ];
    }
}
