<?php

namespace App\Listeners;

use App\Model\Admin\SystemUser;
use App\Servers\PermissionServer;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogAuthenticationAttempt
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Attempting $event
     *
     * @return void
     */
    public function handle(Attempting $event)
    {
        if ($event->guard == 'admin') {
            $systemUser =
                SystemUser::where('username', $event->credentials['username'])
                    ->first();
            if ($systemUser) {
                $allowLogin =
                    permissionserver::allowLogin($systemUser->id, true, false,
                                                 $event->guard);
                if (!$allowLogin['status']) {
                    die(json_encode([
                                        'status_code' => 400,
                                        'message' => $allowLogin['message']
                                    ]));
                    die;
                }
            } else {
                die(json_encode([
                                    'status_code' => 400,
                                    'message' => '用户名或密码错误'
                                ]));
            }
        }

    }
}
