<?php

namespace App\Services\Tagihan;

use DB;

class TagihanService
{
    public function GetTagihanById($idTagihan){
        return DB::connection('H2H')->table('ca_tagihan')->where([['idTagihan',$idTagihan]])->first();
    }

    public function GetTagihanDetilById($idTagihan){
        return DB::connection('H2H')->table('ca_tagihan_detil')->where('idTagihan',$idTagihan)->get();
    }

}