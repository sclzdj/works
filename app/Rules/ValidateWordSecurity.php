<?php

namespace App\Rules;

use App\Servers\WechatServer;
use Illuminate\Contracts\Validation\Rule;

class ValidateWordSecurity implements Rule
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
        return WechatServer::checkContentSecurity($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '含有非法字符！';
    }
}
