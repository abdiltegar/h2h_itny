<?php

namespace App\Services\Auth;

use DB;

class UserService
{
    public function AuthenticationUser($emailAdmin, $passwordAdmin){
        $res = false;

        $check_user = DB::connection('SIA')->table('_user')->where([['email',$emailAdmin],['password',$passwordAdmin]])->count();
        if ($check_user > 0) {
            $res = true;
        }

        return $res;
    }
}
