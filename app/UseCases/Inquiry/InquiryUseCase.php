<?php

namespace App\UseCases\Inquiry;

use App\Services\Student\StudentService;
use App\Services\Inquiry\InquiryService;

class InquiryUseCase
{
    public function InquiryUseCase($nomorPembayaran){
        $kodeBayar = substr($nomorPembayaran,0,1);
        $nimRegnum = substr($nomorPembayaran,1);

        $stdServ = new StudentService();
        $student = $stdServ->GetStudentByRegNum($nimRegnum);
        if($student->nama != null){
            $nimRegnum = $student->registerNumber;
        }

        $inqServ = new InquiryService();
        $inquiry = $inqServ->InquiryService($nimRegnum, $kodeBayar);

        return $inquiry;
    }
}
