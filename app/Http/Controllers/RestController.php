<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UseCases\Inquiry\InquiryUseCase;
use App\UseCases\Payment\PaymentUseCase;
use App\UseCases\Bank\BankUseCase;
use App\UseCases\Log\LogUseCase;
use App\Models\DTOTagihanResponse;
use App\Models\DTOPaymentResponse;
use App\Models\ResponseCode;

class Test {
    public $nama;
}

class RestController extends Controller
{
    private $kodeBankLocal = "999x";
    private $passwordBankLocal = "Bank@123";
    private $univName = "Institut Teknologi Nasional Yogyakarta";

    function Inquiry(Request $request) {
        // TODO get parameters
        $kodeBank = $request->kodeBank;
        $passwordBank = $request->passwordBank;
        $kodeChannel = $request->kodeChannel;
        $kodeTerminal = $request->kodeTerminal;
        $nomorPembayaran = $request->nomorPembayaran;
        $tanggalTransaksi = $request->tanggalTransaksi;
        $idTransaksi = $request->idTransaksi;

        // TODO Prepare response
        // $res = new DTOTagihanResponse();
        $res = new \stdClass();
        $resCode = new ResponseCode();

        // TODO Authentication Bank
        $bankUseCase = new BankUseCase();
        $auth = $bankUseCase->AuthenticationBank($kodeBank, $passwordBank);
    
        if($auth){

            // TODO Process Inquiry
            $useCase = new InquiryUseCase();
            $resUseCase = $useCase->InquiryUseCase($nomorPembayaran);

            $res->idTagihan = $resUseCase->idTagihan;
            $res->nama = $resUseCase->nama;
            $res->fakultas = $resUseCase->fakultas;
            $res->jurusan = $resUseCase->jurusan;
            $res->angkatan = $resUseCase->angkatan;
            $res->code = $resUseCase->code;
            $res->message = $resUseCase->message;
            $res->totalNominal = $resUseCase->totalNominal;
            $res->deskripsi = $resUseCase->deskripsi;

        } else {
            $res->code = $resCode->ERR_BANK_UNKNOWN;
            $res->message = "Identitas collecting agent tidak dikenal.";
        }

        $log = new LogUseCase();
        $log->LogInquiry((isset($res->idTagihan) ? $res->idTagihan : ""), $kodeBank, $kodeChannel, $kodeTerminal, $nomorPembayaran, $tanggalTransaksi, $idTransaksi, $resCode->mappingRCToDB($res->code), $res->message);
        return response()->json($res);
    }

    function Payment(Request $request){
        // TODO get parameters
        $idTagihan = $request->idTagihan;
        $kodeBank = $request->kodeBank;
        $passwordBank = $request->passwordBank;
        $kodeChannel = $request->kodeChannel;
        $kodeTerminal = $request->kodeTerminal;
        $nomorPembayaran = $request->nomorPembayaran;
        $waktuTransaksiBank = $request->waktuTransaksiBank;
        $totalNominal = $request->totalNominal;
        $kodeUnikBank = $request->kodeUnikBank;
        $nomorJurnalBank = $request->nomorJurnalBank;
        $passwordAdmin = $request->passwordAdmin;
        $emailAdmin = $request->emailAdmin;
        $petugasLogin = $request->petugasLogin;
        $catatan = $request->catatan;

        // TODO Prepare response
        $resCode = new ResponseCode();

        $res = new DTOPaymentResponse();
        $res->idTagihan = $idTagihan;
        $res->nomorPembayaran = $nomorPembayaran;
        $res->totalNominal = $totalNominal;

        // TODO Authentication Bank
        $bankUseCase = new BankUseCase();
        $auth = $bankUseCase->AuthenticationBank($kodeBank, $passwordBank);
    
        if($auth){

            // TODO Process Payment
            $useCase = new PaymentUseCase();
            $resUseCase = $useCase->PaymentUseCase($idTagihan, $nomorPembayaran, $kodeUnikBank, $nomorJurnalBank, $tanggalTransaksi, $kodeBank, $kodeChannel, $kodeTerminal, $totalNominal, $petugasLogin, $catatan);

            $res = $resUseCase;

        } else {
            $res->code = $resCode->ERR_BANK_UNKNOWN;
            $res->message = "Identitas collecting agent tidak dikenal.";
        }

        $log = new LogUseCase();
        $log->LogPayment($idTagihan, $kodeBank, $kodeChannel, $kodeTerminal, $nomorPembayaran, $tanggalTransaksi, $idTransaksi, $resCode->mappingRCToDB($res->code), $res->message);
        return response()->json($res);
    }

    function test() {
        return response()->json($this->get());
    }

    function get(){
        $test = new Test();
    
        return $test;
    }
}
