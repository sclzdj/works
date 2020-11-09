<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class InviteSetting extends Model
{
    public $table = "invite_settings";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];


    static public function getName(){
        return [

        ];
    }

    public static function getMedal($photographer_id){
        $num = InviteList::where(['parent_photographer_id' =>$photographer_id])->count();
        $settings = InviteSetting::find(1);
        try {
            $medaljson = json_decode($settings['cloudmedal'], true);
        }catch (\Exception $exception){
            return false;
        }
        foreach ($medaljson as $key => $value){
            if ($value['number'] > $num){
                return [
                    'medal' => $key,
                    'value' => $value
                ];
            }
        }


    }


}
