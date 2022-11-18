<?php

namespace App\UseCases\Bank;

use App\Services\Bank\BankService;

class BankUseCase
{
    public function AuthenticationBank($kodeBank, $passwordBank){

        $serv = new BankService();
        $Bank = $serv->AuthenticationBank($kodeBank, \md5($passwordBank));

        return $Bank;
    }

    public function GetByKodeBank($kodeBank){
        $serv = new BankService();
        return $serv->GetByKodeBank($kodeBank);
    }

    public function CheckSumBsi($kodeBank, $secretKey, $nomorPembayaran, $tanggalTransaksi, $totalNominal, $checksum){
        $serv = new BankService();
        
        $string = "".$nomorPembayaran."".$secretKey."".$tanggalTransaksi."".$totalNominal."";
        if($checksum == \sha1($string)){
            return true;
        }
        return false;
    }

    public function CheckSumBmi($kodeBank, $secretKey, $nomorPembayaran, $tanggalTransaksi, $totalNominal, $checksum){
        $serv = new BankService();

        $string = "".$nomorPembayaran."".$secretKey."".$tanggalTransaksi."".$totalNominal."";
        if($checksum == \md5($string)){
            return true;
        }
        return false;
    }
}
