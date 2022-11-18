<?php

namespace App\Services\Student;

use DB;

class StudentResponse {
    public $registerNumber;
    public $nim;
    public $nama;
    public $fakultas;
    public $jurusan;
    public $angkatan;
    public $email;
    public $notelp;
}

class StudentService
{
    public function GetStudentByRegNum($regNum){
        $res = new StudentResponse();

        $data = DB::connection('SIA')
            ->table('acd_student')
            ->leftJoin('mstr_department', 'acd_student.Department_Id', '=', 'mstr_department.Department_Id')
            ->leftJoin('mstr_faculty', 'mstr_department.Faculty_Id', '=', 'mstr_faculty.Faculty_Id')
            ->Where([['Register_Number',$regNum]])
            ->first();

        if($data != null){
            $res->registerNumber = $data->Register_Number;
            $res->nim = $data->Nim;
            $res->nama = $data->Full_Name;
            $res->fakultas = $data->Faculty_Name;
            $res->jurusan = $data->Department_Name;
            $res->angkatan = $data->Entry_Year_Id;
            $res->email = $data->Email_General;
            $res->notelp = $data->Phone_Mobile;
        }

        return $res;
    }

    public function GetStudentByNim($nim){
        $res = new StudentResponse();

        $data = DB::connection('SIA')
            ->table('acd_student')
            ->leftJoin('mstr_department', 'acd_student.Department_Id', '=', 'mstr_department.Department_Id')
            ->leftJoin('mstr_faculty', 'mstr_department.Faculty_Id', '=', 'mstr_faculty.Faculty_Id')
            ->Where([['Nim',$nim]])
            ->first();
            
        if($data != null){
            $res->registerNumber = $data->Register_Number;
            $res->nim = $data->Nim;
            $res->nama = $data->Full_Name;
            $res->fakultas = $data->Faculty_Name;
            $res->jurusan = $data->Department_Name;
            $res->angkatan = $data->Entry_Year_Id;
            $res->email = $data->Email_General;
            $res->notelp = $data->Phone_Mobile;
        }

        return $res;
    }

    public function GetCamaruByRegNum($regNum){
        $res = new StudentResponse();

        $data = DB::connection('SIA')->table('reg_camaru')->Where([['Reg_Num',$regNum]])->first();
            
        if($data != null){
            $res->registerNumber = $data->Reg_Num;
            $res->nim = "";
            $res->nama = $data->Full_Name;
            $res->fakultas = "";
            $res->jurusan = "";
            $res->angkatan = $data->Entry_Year_Id;
            $res->email = $data->Email_General;
            $res->notelp = $data->Phone_Mobile;
        }

        return $res;
    }

    public function UpdateHerStatus($regNum){
        $data_camaru = DB::connection('SIA')->table('reg_camaru')->where('Reg_Num',$nim)->first();
        if ($data_camaru == null) {
            return false;
        }
        return DB::connection('SIA')->table('reg_camaru')->where('Reg_Num',$nim)->update(['Her_Status'=>1]);
    }
}