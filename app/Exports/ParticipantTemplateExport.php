<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ParticipantTemplateExport implements FromArray, ShouldAutoSize
{
    public function array(): array
    {
        return [['Nomor Peserta', 'NIK', 'Nama Lengkap', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'Nomor WhatsApp', 'Email', 'Program Pelatihan', 'Angkatan', 'Kelas', 'Status'], ['CONTOH-001', '3174000000000001', 'Nama Contoh', 'L', 'Jakarta', '2000-01-31', 'Alamat lengkap', '081234567890', 'contoh@email.test', 'Nama Program', 'Angkatan 1', 'Kelas A', 'aktif']];
    }
}
