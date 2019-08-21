<?php

namespace App\Http\Middleware;

use App\Servers\PermissionServer;
use Closure;

class PermisssionAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if(!PermissionServer::website()){
            $allowLoginOne=PermissionServer::allowLoginOne(true);
            if($allowLoginOne['status']){
                if(PermissionServer::allowActionOne()){
                    return $next($request);
                }else{
                    return abort(403,'没有权限');
                }
            }else{
                auth('admin')->logout();

                return abort(401,$allowLoginOne['message']);
            }
        }else{
            return $next($request);
        }
    }
}
