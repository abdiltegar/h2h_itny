<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\UseCases\Inquiry\InquiryUseCase;
use App\UseCases\Payment\PaymentUseCase;
use App\UseCases\Bank\BankUseCase;
use App\UseCases\Log\LogUseCase;
use App\Models\ResponseCode;
use App\Helpers\Bni\BniEnc;

class BniController extends Controller
{
    //
    protected $kodeBank = "bnie";
    protected $passwordBank = "Bank@123";
    protected $kodeChannel = "va";
    protected $kodeTerminal = "va";
    protected $idTransaksi = 1;

    function Inquiry(Request $request) {
        // Prepare untuk response
        $res = new \stdClass();
        $resCode = new ResponseCode();
        
        // TODO Validasi parameters
        $validator = Validator::make($request->all(), [
            'nomorPembayaran' => 'required'
        ]);

        if ($validator->fails()) {
            $res->status = $resCode->ERR_PARSING_MESSAGE;
            $res->message = "Invalid messaging format";

            return response()->json($res);
        }

        // TODO get parameters
        $kodeBank = $this->kodeBank;
        $kodeChannel = $this->kodeChannel;
        $kodeTerminal = $this->kodeTerminal;
        $nomorPembayaran = $request->nomorPembayaran;

        // TODO Authentication Bank
        $bankUseCase = new BankUseCase();
        $totalNominalStr = "";
        if($action == "payment"){
            $totalNominalStr = $totalNominal;
        }

        $bank = $bankUseCase->GetByKodeBank($kodeBank);
        if($bank == null) {
            $res->status = $resCode->ERR_BANK_UNKNOWN;
            $res->message = "Identitas collecting agent tidak dikenal.";

            return response()->json($res);
        }

        $useCase = new InquiryUseCase();
        $resUseCase = $useCase->InquiryUseCase($nomorPembayaran);

        if ($resUseCase->code != $resCode->OK){
            $res->status = $resUseCase->code;
            $res->message = $resUseCase->message;
        }

        $bniUseCase = new BniUseCase();
        // Check is create or only update
        if($bniUseCase->CheckIsCreateVA()){
            // TODO Create VA
            $trxId = $resUseCase->idTagihan;
            $trxAmount = $resUseCase->totalNominal;
            $description = $resUseCase->deskripsi;
            
            $resBniUseCase = $bniUseCase->CreateVa($trxId, $trxAmount, $nomorPembayaran, $description);
        }else{
            // TODO Update VA
            $trxId = $resUseCase->idTagihan;
            $trxAmount = $resUseCase->totalNominal;
            $description = $resUseCase->deskripsi;

            $resBniUseCase = $bniUseCase->UpdateVa($trxId, $trxAmount, $nomorPembayaran, $description);
        }
        

        return response()->json($res);
        
    }

    public function CallBack(Request $request){
        try{
            
            $response = new \stdClass();
            // $str = "[".date("d-m-Y H:i:s")."] : di akses oleh - ".$this->get_client_ip(). " - data : ".json_decode($request->all());
            // $file_log="../log_access/log_".date("Ymd").".txt";

            // // cek file log
            // if (!file_exists($file_log)) {
            //     $make=fopen($file_log, 'w');
            // }

            // // akses file log
            // $akses=fopen($file_log, 'a');
            // fwrite($akses, "$str\n");
            // fclose($akses);

            $data = file_get_contents('php://input');

            $dataJson = json_decode($data, true);
            if (!$dataJson) {
                $response->status = "999";
                $response->message = "jangan iseng :D";
            }else{
                if ($dataJson['client_id'] == $this->clientId) {
                    try{
                        $data_asli = BniEnc::decrypt($dataJson['data'], $this->clientId, $this->secretKey);
                        // dd($data_asli);
                        if (!$data_asli) {
                            $response->status = "999";
                            $response->message = "waktu server tidak sesuai NTP atau secret key salah.";
                        } else {
                            // keperluan testing
                            if($data_asli['virtual_account'] == "9881604421200014"){
                                $response->status = "999";
                                $response->message = "Tagihan tidak ditemukan";
                            }else{
                                // echo '{"status":"000"}';
                                try{
                                    $trx_id = $data_asli['trx_id'];
                                    $virtual_account = $data_asli['virtual_account'];
                                    $customer_name = $data_asli['customer_name'];
                                    $trx_amount = $data_asli['trx_amount'];
                                    $payment_amount = $data_asli['payment_amount'];
                                    $cumulative_payment_amount = $data_asli['cumulative_payment_amount'];
                                    $payment_ntb = $data_asli['payment_ntb'];
                                    $datetime_payment = $data_asli['datetime_payment'];
                                    $datetime_payment_iso8601 = $data_asli['datetime_payment_iso8601'];
                                    $kode_bayar = substr($trx_id,0,1);

                                    $caTagihan = DB::connection('H2H')->table('ca_tagihan')
                                    ->where([['idTagihan',$trx_id]])
                                    ->first();

                                    $payment_lokal = \App::call('App\Http\Controllers\RestBniController@Payment', [
                                        "idTagihan" => $trx_id,
                                        "kodeBank" => $this->kodeBank,
                                        "passwordBank" => $this->passwordBank,
                                        "kodeChannel" => $this->kodeChannel,
                                        "kodeTerminal" => $this->kodeTerminal,
                                        "nomorPembayaran" => $caTagihan->nomorPembayaran,
                                        "waktuTransaksiBank" => Date('YmdHis',strtotime($datetime_payment)),
                                        "totalNominal" => $payment_amount,
                                        "kodeUnikBank" => "bnie",
                                        "nomorJurnalBank" => 1,
                                        "catatan" => "Pembayaran BNI E-Collection dengan nomor va ".$virtual_account,
                                        "nomorVa" => $virtual_account
                                    ]);
                                    
                                    $insertEpayment = $this->updateEpayment($caTagihan->registerNumber, $virtual_account, $trx_amount, true);

                                    $response_payment = $payment_lokal->original;

                                    $response->status = $response_payment->code == "0" ? "000" : $response_payment->code;
                                    $response->message = $response_payment->message;
                                }catch(Exception $e){
                                    $response->status = "999";
                                    $response->message = "".$e->getMessage();
                                }
                            }
                        }
                    }catch(Exception $e){
                        $response->status = "009";
                        $response->message = "".$e->getMessage();
                    }
                }else{
                    $response->status = "999";
                    $response->message = "client id not found";
                }
            }
        } catch (\Exception $e) {
            $response->status = "009";
            $response->message = "".$e->getMessage();
        }

        return response()->json($response);
    }
    
}
