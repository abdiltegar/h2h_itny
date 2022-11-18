<?php

namespace App\UseCases\Bni;

use App\Services\Student\StudentService;
use App\Services\Tagihan\TagihanService;
use App\Helpers\Bni\BniEnc;
use DB;

class BniUseCase
{
    protected $prefix = "988";
    protected $clientId = "71213";
    protected $secretKey = "9ef61a03c91e750ef90bd6668715f6c7";
    protected $url = "https://api.bni-ecollection.com";
    protected $kodeDepanVa = "98871213";
    protected $yptn = "YPTN";
    
    public function CreateVa($trxId, $trxAmount, $nomorPembayaran, $description){
        $stdServ = new StudentService();
        $tghnServ = new TagihanService();

        try {
            $customerName = "";
            $customerEmail = "";
            $customerPhone = "";
            $nim = "";

            $kodeBayar = substr($nomorPembayaran,0,1);
            $nim = substr($nomorPembayaran,1);

            $mhs = $stdServ->GetCamaruByRegNum($nimRegnum);
            if($mhs->nama != null){
                $customerName = $mhs->nama;
                $customerEmail = $mhs->email;
                $customerPhone = $mhs->notelp;
                $nim = $mhs->registerNumber;
            } else {
                $mhs = $stdServ->GetStudentByRegNum($nimRegnum);
                if($mhs->nama != null){
                    $customerName = $mhs->nama;
                    $customerEmail = $mhs->nim."@students.itny.ac.id";
                    $customerPhone = $mhs->notelp;
                    $nim = $mhs->nim;
                } else {
                    $mhs = $stdServ->GetStudentByNim($nimRegnum);
                    if($mhs->nama != null){
                        $customerName = $mhs->nama;
                        $customerEmail = $mhs->nim."@students.itny.ac.id";
                        $customerPhone = $mhs->notelp;
                        $nim = $mhs->nim;
                    }
                }
            }

            $virtualAccount = $this->GenerateVa($idTagihan);

            // Exists
            $originalData = array(
                'type' => 'createbilling',
                'client_id' => $this->clientId,
                'trx_id' => $trxId,
                'trx_amount' => $trxAmount,
                'billing_type' => 'c',
                'customer_name' => $this->yptn." - ".$nim." ".$customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'virtual_account' => $virtualAccount,
                'datetime_expired' => date('c', time() + 24 * 3600),
                'description' => $description
            );
    
            $hashedString = BniEnc::encrypt(
                $originalData,
                $this->clientId,
                $this->secretKey
            );

            $data = array(
                'client_id' => $this->clientId,
                'data' => $hashedString
            );
            
            $responseJson = $this->get_content($this->url, json_encode($data));
            $response = json_decode($responseJson, true);
    
            if ($response['status'] !== '000') {
                $json = $response;

                $json['originalData'] = $originalData;
                $json['hashedString'] = $hashedString;

                $update_ca_tagihan = DB::connection('H2H')->table('ca_tagihan')->where('idTagihan',$trxId)->update(['nomorVa' => $virtualAccount]);
                $insertEpayment = $this->insertEpayment($mhs->registerNumber, $virtualAccount, $trxAmount);

            } else {
                $dataResponse['status'] = $response['status'];
                $dataResponse['data'] = BniEnc::decrypt($response['data'], $this->clientId, $this->secretKey);
                $json = $dataResponse;

                $json['originalData'] = $originalData;
                $json['hashedString'] = $hashedString;
            }
        } catch (\Exception $e) {
            $json['status'] = '999';
            $json['message'] = "Error : ".$e->getMessage();
        }
        return response()->json($json);
    }

    public function UpdateVa($trxId, $trxAmount, $nomorPembayaran, $description){
        try {
            $customerName = "";
            $customerEmail = "";
            $customerPhone = "";
            $nim = "";

            $kodeBayar = substr($nomorPembayaran,0,1);
            $nim = substr($nomorPembayaran,1);

            $stdServ = new StudentService();

            $mhs = $stdServ->GetCamaruByRegNum($nimRegnum);
            if($mhs->nama != null){
                $customerName = $mhs->nama;
                $customerEmail = $mhs->email;
                $customerPhone = $mhs->notelp;
                $nim = $student->registerNumber;
            } else {
                $mhs = $stdServ->GetStudentByRegNum($nimRegnum);
                if($mhs->nama != null){
                    $customerName = $mhs->nama;
                    $customerEmail = $mhs->nim."@students.itny.ac.id";
                    $customerPhone = $mhs->notelp;
                    $nim = $student->nim;
                } else {
                    $mhs = $stdServ->GetStudentByNim($nimRegnum);
                    if($mhs->nama != null){
                        $customerName = $mhs->nama;
                        $customerEmail = $mhs->nim."@students.itny.ac.id";
                        $customerPhone = $mhs->notelp;
                        $nim = $student->nim;
                    }
                }
            }

            $caTagihanExist = DB::connection('H2H')->table('ca_tagihan')
                        ->where([
                            ['nomorPembayaran',$nomorPembayaran],
                            ['idTagihan','!=',$trxId]
                        ])
                        ->orderBy('ts','desc')->first();

            $virtualAccount = $caTagihanExist->nomorVa;

            $originalData = array(
                'type' => 'updatebilling',
                'client_id' => $this->clientId,
                'trx_id' => $trxId,
                'trx_amount' => $trxAmount,
                'customer_name' => $this->yptn." - ".$nim." ".$customerName,
                'description' => $description
            );
    
            $hashedString = BniEnc::encrypt(
                $originalData,
                $this->clientId,
                $this->secretKey
            );
    
            $data = array(
                'client_id' => $this->clientId,
                'data' => $hashedString
            );
    
            $response = $this->get_content($this->url, json_encode($data));
            $responseJson = json_decode($response, true);
    
            if ($responseJson['status'] !== '000') {
                // handling jika gagal
                $json = $responseJson;
                // delete ca_tagihan lama , dan rubah idTagihan dari ca_tagihan yang baru dan detail nya.
                $delete_ca_tagihan_detil_lama = DB::connection('H2H')->table('ca_tagihan_detil')->where('idTagihan',$caTagihanExist->idTagihan)->delete();
                $delete_ca_tagihan_lama = DB::connection('H2H')->table('ca_tagihan')->where('idTagihan',$caTagihanExist->idTagihan)->delete();
                $update_ca_tagihan_baru = DB::connection('H2H')->table('ca_tagihan')->where('idTagihan',$caTagihanExist->idTagihan)->update(['idTagihan' => $caTagihanExist->idTagihan, 'nomorVa' => $caTagihanExist->nomorVa]);
                $update_ca_tagihan_detil_baru = DB::connection('H2H')->table('ca_tagihan_detil')->where('idTagihan',$caTagihanExist->idTagihan)->update(['idTagihan' => $caTagihanExist->idTagihan]);

                $updateEpayment = $this->updateEpayment($caTagihanExist->registerNumber, $caTagihanExist->nomorVa, $trxAmount, null);

            } else {
                $dataResponse['status'] = $responseJson['status'];
                $dataResponse['data'] = BniEnc::decrypt($responseJson['data'], $this->clientId, $this->secretKey);
                $json = $dataResponse;
            }
        } catch (\Exception $e) {
            $json['status'] = '999';
            $json['message'] = "Error : ".$e->getMessage();
        }
        return response()->json($json);
    }

    public function GenerateVa($idTagihan){
        //generate va-------------
        
        $caTagihan = DB::connection('H2H')->table('ca_tagihan')
                        ->where([['idTagihan',$idTagihan]])
                        ->first();

        $semester = substr($caTagihan->periode,2,3);
        $nomorVa = $this->kodeDepanVa."".$semester."00001";
        $caTagihanExistVa = DB::connection('H2H')->table('ca_tagihan')
                            ->where([
                                ['nomorVa','like',''.($this->kodeDepanVa."".$semester).'%']
                            ])
                            ->orderBy('nomorVa','desc')
                            ->first();
        if($caTagihanExistVa != null){
            $nomorVa = ((int)$caTagihanExistVa->nomorVa)+1;
        }

        return $nomorVa;
        //akhir generate va-------
    }

    public function CheckIsCreateVA($nomorPembayaran){
        $is_create = true;//untuk flag apakah perlu membuat va baru ke bni e collection

        //cek di ca tagihan ada nomor va yang belum dibayar atau tidak
        $caTagihanExist = DB::connection('H2H')->table('ca_tagihan')
                    ->where([
                        ['nomorPembayaran',$nomorPembayaran],
                        ['nomorVa','!=',null]
                    ])
                    ->orderBy('ts','desc')->first();
        
        // dd($caTagihanExist);

        if($caTagihanExist != null){
            $caPaymentExist = DB::connection('H2H')->table('ca_payment')->where([['idTagihan',$caTagihanExist->idTagihan]])->first();
            if($caPaymentExist == null){
                $is_create = false;
            }
        }

        return $is_create;
        // akhir cek
    }

    public function insertEpayment($register_number, $va_number, $amount){
        $expired_date = date('c', time() + 24 * 3600);

        $create = DB::connection('SIA')->table('fnc_epayment')->insert([
            'Register_Number' => $register_number,
            'Va_Number' => $va_number,
            'Expired_Date' => $expired_date,
            'Amount' => $amount,
            'Is_Paid' => false,
            'Created_By' => 'system',
            'Created_Date' => Date('Y-m-d H:i:s')
        ]);

        return $create;
    }

    public function updateEpayment($register_number, $va_number, $amount, $is_paid){
        $expired_date = date('c', time() + 24 * 3600);

        if($is_paid != null){
            $latest_epayment = DB::connection('SIA')->table('fnc_epayment')->where([['register_number',$register_number],['Va_Number', $va_number]])->orderBy('Epayment_Id','desc')->first();

            $update = DB::connection('SIA')->table('fnc_epayment')->where('Epayment_Id', $latest_epayment->Epayment_Id)->update([
                'Is_Paid' => $is_paid,
                'Modified_By' => 'system',
                'Modified_Date' => Date('Y-m-d H:i:s')
            ]);
        }else{
            $latest_epayment = DB::connection('SIA')->table('fnc_epayment')->where([['register_number',$register_number]])->orderBy('Epayment_Id','desc')->first();

            $update = DB::connection('SIA')->table('fnc_epayment')->where('Epayment_Id', $latest_epayment->Epayment_Id)->update([
                'Va_Number' => $va_number,
                'Amount' => $amount,
                'Modified_By' => 'system',
                'Modified_Date' => Date('Y-m-d H:i:s')
            ]);
        }

        return $update;
    }
}