<?php

namespace App\UseCases\Payment;

use App\Services\Student\StudentService;
use App\Services\Tagihan\TagihanService;
use App\Services\Payment\PaymentService;
use App\Models\DTOPaymentResponse;
use App\Models\ResponseCode;

class PaymentUseCase
{
    public function PaymentUseCase($idTagihan, $nomorPembayaran, $kodeUnikBank, $nomorJurnalBank, $tanggalTransaksi, $kodeBank, $kodeChannel, $kodeTerminal, $totalNominal, $petugasLogin, $catatan){
        $res = new DTOPaymentResponse();
        $resCode = new ResponseCode();

        $tghnServ = new TagihanService();
        $payServ = new PaymentService();
        $stdServ = new StudentService();
        
        $kodeBayar = substr($nomorPembayaran,0,1);
        $nimRegnum = substr($nomorPembayaran,1);

        $rincian = ""; // Based on usp_h2h_inquiry
        switch($kodeBayar){
            case "1":
                $rincian = "TEST MASUK";
                break;
            case "2":
                $rincian = "REGISTRASI MAHASISWA BARU";
                break;
            case "3":
                $rincian = "SPP TETAP";
                break;
            case "4":
                $rincian = "SPP VARIABEL/KRS";
                break;
            default:

                $res->code = $resCode->ERR_NOT_FOUND;
                $res->message = "nomor pembayaran salah";
                $res->nomorPembayaran = $tagihan->nomorPembayaran;
                $res->nomorMahasiswa = $nimRegnum;
                $res->nama = "";
                $res->email = "";
                $res->totalNominal = $tagihan->totalNominal;
                $res->rincian = "";
                $res->idTagihan = $idTagihan;
                
                return $res;

                break;
        }

        // Check student exist
        $stdServ = new StudentService();
        $student = $stdServ->GetCamaruByRegNum($nimRegnum);
        if($student->nama == null){
            $student = $stdServ->GetStudentByNim($nimRegnum);
            if($student->nama == null){
                $student = $stdServ->GetStudentByRegNum($nimRegnum);
            }
        }
        if($student == null){
            $res->code = $resCode->ERR_NOT_FOUND;
            $res->message = "nomor pembayaran salah";
            $res->nomorPembayaran = $tagihan->nomorPembayaran;
            $res->nomorMahasiswa = $nimRegnum;
            $res->nama = "";
            $res->email = "";
            $res->totalNominal = $tagihan->totalNominal;
            $res->rincian = "";
            $res->idTagihan = $idTagihan;

            return $res;
        }

        // Check ada tagihan atau tidak di ca_tagihan
        $tagihan = $tghnServ->GetTagihanById($idTagihan);
        
        if($tagihan->nomorPembayaran != $nomorPembayaran){
            $res->code = $resCode->ERR_NOT_FOUND;
            $res->message = "Data Tagihan Tidak Ditemukan";
            $res->nomorPembayaran = $tagihan->nomorPembayaran;
            $res->nomorMahasiswa = $nimRegnum;
            $res->nama = $student->nama;
            $res->email = $student->email;
            $res->totalNominal = $tagihan->totalNominal;
            $res->rincian = $rincian;
            $res->idTagihan = $idTagihan;

            return $res;
        }
        
        // Check tagihan sudah dibayar atau belum
        $isPaid = $payServ->CheckPaymentStatus($idTagihan, $nomorPembayaran, $kodeBank, $totalNominal);
        if ($isPaid) {
            $res->code = $resCode->ERR_ALREADY_PAID;
            $res->message = "Tagihan sudah dibayar";
            $res->nomorPembayaran = $tagihan->nomorPembayaran;
            $res->nomorMahasiswa = $nimRegnum;
            $res->nama = $student->nama;
            $res->email = $student->email;
            $res->totalNominal = $tagihan->totalNominal;
            $res->rincian = $rincian;
            $res->idTagihan = $idTagihan;
            return $res;
        }

        // Check nominalnya sama atau tidak
        if ($tagihan->totalNominal != $totalNominal) {
            $res->code = $resCode->ERR_PAYMENT_WRONG_AMOUNT;
            $res->message = "Nominal tidak sesuai";
            $res->nomorPembayaran = $tagihan->nomorPembayaran;
            $res->nomorMahasiswa = $nimRegnum;
            $res->nama = $student->nama;
            $res->email = $student->email;
            $res->totalNominal = $tagihan->totalNominal;
            $res->rincian = $rincian;
            $res->idTagihan = $idTagihan;
            return $res;
        }
        
        $paymentReff = $payServ->InsertReffPayment($idTagihan, $kodeUnikBank, $nomorJurnalBank, $tanggalTransaksi, $kodeBank, $kodeChannel, $kodeTerminal, $totalNominal, $kodeBayar);
        if($paymentReff->code != $resCode->OK){
            return $paymentReff;
        }
        $paymentStud = $payServ->InsertStudentPayment($idTagihan, $paymentReff->lastId, $kodeBank, $catatan, $petugasLogin, $kodeBayar);
        if($paymentStud->code != $resCode->OK){
            return $paymentStud;
        }

        $res->code = $resCode->OK;
        $res->message = "Transaksi berhasil";
        $res->nomorPembayaran = $tagihan->nomorPembayaran;
        $res->nomorMahasiswa = $nimRegnum;
        $res->nama = $student->nama;
        $res->email = $student->email;
        $res->totalNominal = $tagihan->totalNominal;
        $res->rincian = $rincian;
        $res->idTagihan = $idTagihan;
        return $res;
    }
}
