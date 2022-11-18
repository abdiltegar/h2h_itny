<?php

namespace App\UseCases\Log;

use App\Services\Log\LogService;

class LogUseCase
{
    public function LogInquiry($idTagihan, $kodeBank, $kodeChannel, $kodeTerminal, $nomorPembayaran, $tanggalTransaksi, $idTransaksi, $code, $message){

        $inqServ = new LogService();
        $Log = $inqServ->LogInquiry($idTagihan, $kodeBank, $kodeChannel, $kodeTerminal, $nomorPembayaran, $tanggalTransaksi, $idTransaksi, $code, $message);

        return $Log;
    }
}
