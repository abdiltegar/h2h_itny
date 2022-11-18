<?php

namespace App\Services\Payment;

use DB;
use App\Services\Tagihan\TagihanService;
use App\Models\DTOPaymentResponse;
use App\Models\ResponseCode;

class PaymentService
{
    public function InsertReffPayment($idTagihan, $kodeUnikBank, $nomorJurnalBank, $tanggalTransaksi, $kodeBank, $kodeChannel, $kodeTerminal, $totalNominal, $kodeBayar){
        $res = new \stdClass();
        $resCode = new ResponseCode();
        $tghnServ = new TagihanService();

        $tagihan = $tghnServ->GetTagihanById($idTagihan);

        $status_bayar = 1;
        // CALL sp_online_insert_payment to insert into ca_payment from ca_tagihan
        $spInsert = DB::connection('H2H')->select("CALL sp_online_insert_payment(?,?,?,?,?,?,?,?,?,?,?)",[$idTagihan,$tagihan->nomorPembayaran,$kodeUnikBank,$nomorJurnalBank,$tanggalTransaksi,$kodeBank,$kodeChannel,$kodeTerminal,$totalNominal,$status_bayar,'']);

        if (!$spInsert[0]->cek = 1) {
            $res->code = $resCode->ERR_UNDEFINED;
            $res->message = "Gagal input transaksi pembayaran ke reff payment";
            return $res;
        }

        $year = substr($tanggalTransaksi,0,4);
        $month = substr($tanggalTransaksi,4,2);
        $day = substr($tanggalTransaksi,6,2);
        $hour = substr($tanggalTransaksi,8,2);
        $minute = substr($tanggalTransaksi,10,2);
        $second = substr($tanggalTransaksi,12,2);

        // TODO insert to fnc_reff_payment (table fnc_reff_payment parent dari fnc_student_payment)

        $Reff_Payment_Code = $idTagihan;
        $Register_Number = $tagihan->registerNumber;
        $Payment_Date = Date("Y-m-d H:i:s");
        if($tanggalTransaksi != null && $tanggalTransaksi != ""){
            $Payment_Date = Date("Y-m-d H:i:s",strtotime($year."-".$month."-".$day." ".$hour.":".$minute.":".$second));
        }
        $Bank_Id = $kodeBank;
        $Description = 'online';
        $Term_Year_Id = $tagihan->periode;
        $Total_Amount = $totalNominal;
        $Created_Date = Date("Y-m-d H:i:s");
        $Created_By ='h2h';

        $insert_reff_payment = DB::connection('SIA')->table('fnc_reff_payment')->insertGetId([
            'Reff_Payment_Code' => $Reff_Payment_Code,
            'Register_Number' => $Register_Number,
            'Payment_Date' => $Payment_Date,
            'Bank_Id' => $Bank_Id,
            'Description' => $Description,
            'Term_Year_Id' => $Term_Year_Id,
            'Total_Amount' => $Total_Amount,
            'Created_Date' => $Created_Date,
            'Created_By' => $Created_By
        ]);

        if ($insert_reff_payment == null) {
            $res->code = $resCode->ERR_UNDEFINED;
            $res->message = "Gagal input transaksi pembayaran ke reff payment";
            return $res;
        }

        $last_id = $insert_reff_payment;

        //jika pembayaran pendaftaran maka diinput juga ke reg_camaru reff_payment (keperluan pmb)
        if($kodeBayar == 1){
            $update_reg_camaru = DB::connection('SIA')->table('reg_camaru')->where('Reg_Num',$Register_Number)->update(['Reff_Payment' => $last_id]);
        }

        //jika pembayaran her registrasi update is_her (keperluan pmb)
        if ($kodeBayar == 2) {
            $update_reg_camaru = DB::connection('SIA')->table('reg_camaru')->where('Reg_Num',$Register_Number)->update(['Her_Status'=>1]);
        }

        $res->code = $resCode->OK;
        $res->message = "Transaksi berhasil";
        $res->lastId = $last_id;
        return $res;
    }

    public function InsertStudentPayment($idTagihan, $reffId, $kodeBank, $catatan, $petugasLogin, $kodeBayar){
        $res = new DTOPaymentResponse();
        $tghnServ = new TagihanService();
        $resCode = new ResponseCode();

        $tagihan = $tghnServ->GetTagihanById($idTagihan);
        $tagihanDetails = $tghnServ->GetTagihanDetilById($idTagihan);
        $i = 1;
        foreach($tagihanDetails as $tagihanDetail) {
            $Reff_Payment_Id = $reffId;
            $Trans_Order = $i;
            $Register_Number = $tagihan->registerNumber;
            $Term_Year_Id = $tagihan->periode;
            $Cost_Item_Id = $tagihanDetail->Cost_Item_Id;
            $Payment_Amount = $tagihanDetail->Nominal;
            $Payment_Status = '1';
            $Bank_Id = $kodeBank;
            $Term_Year_Bill_Id = $tagihanDetail->Term_Year_Bill_Id;
            $Created_By = 'online';
            $Created_Date = Date('Y-m-d H:i:s');
            $Description= null;
            if ($catatan != null) {
                $Description = $catatan;
            }
            if ($petugasLogin != null) {
                $Created_By = $petugasLogin;
            }

            if ($tagihanDetail->Payment_Order != null){
                $Installment_Order = $tagihanDetail->Payment_Order;
                //jika pembayaran her registrasi
                if ($kodeBayar == 2) {
                    //insert juga Is_Her
                    $insert_student_payment = DB::connection('SIA')->table('fnc_student_payment')->insert([
                        'Reff_Payment_Id' => $Reff_Payment_Id,
                        'Trans_Order' => $Trans_Order,
                        'Term_Year_Id' => $Term_Year_Id,
                        'Register_Number' => $Register_Number,
                        'Cost_Item_Id' => $Cost_Item_Id,
                        'Installment_Order' => $Installment_Order,
                        'Payment_Amount' => $Payment_Amount,
                        'Payment_Status' => $Payment_Status,
                        'Bank_Id' => $Bank_Id,
                        'Term_Year_Bill_Id' => $Term_Year_Bill_Id,
                        'Created_By' => $Created_By,
                        'Created_Date' => $Created_Date,
                        'Description' => $Description,
                        'Is_Her' => 1
                    ]);   
                } else {
                    $insert_student_payment = DB::connection('SIA')->table('fnc_student_payment')->insert([
                        'Reff_Payment_Id' => $Reff_Payment_Id,
                        'Trans_Order' => $Trans_Order,
                        'Term_Year_Id' => $Term_Year_Id,
                        'Register_Number' => $Register_Number,
                        'Cost_Item_Id' => $Cost_Item_Id,
                        'Installment_Order' => $Installment_Order,
                        'Payment_Amount' => $Payment_Amount,
                        'Payment_Status' => $Payment_Status,
                        'Bank_Id' => $Bank_Id,
                        'Term_Year_Bill_Id' => $Term_Year_Bill_Id,
                        'Created_By' => $Created_By,
                        'Created_Date' => $Created_Date,
                        'Description' => $Description
                    ]);
                }
                if (!$insert_student_payment) {
                    $res->idTagihan = $idTagihan;
                    $res->nomorPembayaran = $tagihan->nomorPembayaran;
                    $res->totalNominal = $tagihan->totalNominal;
                    $res->code = $resCode->ERR_UNDEFINED;
                    $res->message = "Gagal input transaksi pembayaran ke student payment";
                    return $res;
                }
            } else {
                if ($kode_bayar == 5) {
                    $insert_student_payment = DB::connection('SIA')->table('fnc_student_payment')->insert([
                        'Reff_Payment_Id' => $Reff_Payment_Id,
                        'Trans_Order' => $Trans_Order,
                        'Term_Year_Id' => $Term_Year_Id,
                        'Register_Number' => $Register_Number,
                        'Cost_Item_Id' => $Cost_Item_Id,
                        'Installment_Order' => 1,
                        'Payment_Amount' => $Payment_Amount,
                        'Payment_Status' => $Payment_Status,
                        'Bank_Id' => $Bank_Id,
                        'Term_Year_Bill_Id' => $Term_Year_Bill_Id,
                        'Created_By' => $Created_By,
                        'Created_Date' => $Created_Date,
                        'Description' => $Description
                    ]);
                }else{
                    $insert_student_payment = DB::connection('SIA')->table('fnc_student_payment')->insert([
                        'Reff_Payment_Id' => $Reff_Payment_Id,
                        'Trans_Order' => $Trans_Order,
                        'Term_Year_Id' => $Term_Year_Id,
                        'Register_Number' => $Register_Number,
                        'Cost_Item_Id' => $Cost_Item_Id,
                        'Payment_Amount' => $Payment_Amount,
                        'Payment_Status' => $Payment_Status,
                        'Bank_Id' => $Bank_Id,
                        'Term_Year_Bill_Id' => $Term_Year_Bill_Id,
                        'Created_By' => $Created_By,
                        'Created_Date' => $Created_Date,
                        'Description' => $Description
                    ]);
                }
                if (!$insert_student_payment) {
                    $res->idTagihan = $idTagihan;
                    $res->nomorPembayaran = $tagihan->nomorPembayaran;
                    $res->totalNominal = $tagihan->totalNominal;
                    $res->code = $resCode->ERR_UNDEFINED;
                    $res->message = "Gagal input transaksi pembayaran ke student payment";
                    return $res;
                }
            }
            $i++;
        }
        
        $res->code = $resCode->OK;
        $res->message = "Transaksi berhasil";
        return $res;
    }

    public function CheckPaymentStatus($idTagihan, $nomorPembayaran, $kodeBank, $totalNominal){
        $cekPembayaran2 = DB::connection('H2H')->select("CALL sp_online_cek_payment(?,?,?,?)",[$idTagihan,$nomorPembayaran,$kodeBank,$totalNominal]);
        if ($cekPembayaran2[0]->v_count > 0) {
            return true; // sudah dibayar
        }
        return false; // belum dibayar
    }
}