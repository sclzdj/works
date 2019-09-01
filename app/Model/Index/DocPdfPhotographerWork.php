<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class DocPdfPhotographerWork extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'doc_pdf_id',
        'photographer_work_id',
        'sort',
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
    public static function allowFields() {
        return [
            'id',
            'doc_pdf_id',
            'photographer_work_id',
            'created_at'
        ];
    }
}
