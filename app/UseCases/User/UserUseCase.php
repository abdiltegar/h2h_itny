<?php

namespace App\UseCases\User;

use App\Services\Auth\UserService;

class UserUseCase
{
    public function AuthenticationUser($emailAdmin, $passwordAdmin){

        $serv = new UserService();
        $User = $serv->AuthenticationUser($emailAdmin, $passwordAdmin);

        return $User;
    }
}
