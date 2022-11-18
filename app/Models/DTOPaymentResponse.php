<?php

namespace App\Models;

class DTOPaymentResponse{
    public string $idTagihan;
    public string $nomorPembayaran;
    public string $nomorMahasiswa;
    public string $nama;
    public string $email;
    public int $totalNominal;
    public string $rincian;
    public string $code;
    public string $message;
}