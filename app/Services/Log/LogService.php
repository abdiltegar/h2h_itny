<?php

namespace App\Services\Log;

use DB;

class LogService
{
    public function LogInquiry($idTagihan, $kodeBank, $kodeChannel, $kodeTerminal, $nomorPembayaran, $tanggalTransaksi, $idTransaksi, $code, $message){
        DB::connection('H2H')->select("CALL sp_online_insert_log_inquiry(?,?,?,?,?,?,?,?,?)",[$idTagihan, $kodeBank, $kodeChannel, $kodeTerminal, $nomorPembayaran, $tanggalTransaksi, $idTransaksi, $code, $message]);
    }

    public function LogPayment($idTagihan, $kodeBank, $kodeChannel, $kodeTerminal, $nomorPembayaran, $waktuTransaksiBank, $totalNominal, $kodeUnikBank, $nomorJurnalBank, $code, $message){
        DB::connection('H2H')->select("CALL sp_online_insert_log_payment(?,?,?,?,?,?,?,?,?,?,?)",[$idTagihan,$kodeBank,$kodeChannel,$kodeTerminal,$nomorPembayaran,$waktuTransaksiBank,$totalNominal,$kodeUnikBank,$nomorJurnalBank,$code,$message]);
    }

    public function GetLogInquiryById($idTagihan){
        return DB::connection('H2H')->table('ca_log_inquiry')->where([['idTagihan',$idTagihan]])->first();
    }
}
