<?php

namespace App\Services\Bank;

use DB;

class BankService
{
    public function AuthenticationBank($kodeBank, $passwordBank){
        $res = false;

        $spBankAuth = DB::connection('H2H')->select("CALL sp_online_bank_auth(?,?)",[$kodeBank,$passwordBank]);
        foreach ($spBankAuth as $value) {
            $cek_Bank = $value->cek;
        };
        if ($cek_Bank == 1) {
            $res = true;
        }

        return $res;
    }

    public function GetByKodeBank($kodeBank){
        $bank = DB::connection('H2H')->table('ca_bank')->where('kodeBank', $kodeBank)->first();
        return $bank;
    }
    
}
