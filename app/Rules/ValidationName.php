<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidationName implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (preg_match('/^[\x{4e00}-\x{9fa5}]{0,16}$|^[a-zA-Z0-9]{0,32}$|^[\x{4e00}-\x{9fa5}a-zA-Z0-9]{0,16}$/u', $value)){
            return true;
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '名称验证不通过，只能16位汉字或者32位英文字母';
    }
}
