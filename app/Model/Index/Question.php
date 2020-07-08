<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    public $table = "question";

    public function QuestionUserRelation()
    {
        return $this->belongsToMany(User::class , "question_user");
    }
}
