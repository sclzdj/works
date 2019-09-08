<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class AsyncDocPdfMakeErrorLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'async_doc_pdf_make_id',
        'error_info',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 允许查询的字段
     * @return array
     */
    public static function allowFields()
    {
        return [
            'id',
            'async_doc_pdf_make_id',
            'error_info',
            'created_at',
        ];
    }
}
