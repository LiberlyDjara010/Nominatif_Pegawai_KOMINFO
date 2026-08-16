<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pegawai extends Model
{
    use HasFactory;

    protected $table = 'pegawai';

    public $timestamps = false;

    protected $fillable = [
        'nip', 'nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
        'agama', 'suku', 'kategori_suku', 'status_kepegawaian',
        'pendidikan_terakhir', 'pendidikan_jurusan', 'pangkat_terakhir',
        'golongan', 'jabatan', 'jenis_jabatan', 'status_jabatan', 'unit_organisasi',
        'tmt_jabatan', 'tanggal_masuk', 'tanggal_pangkat_terakhir',
        'masa_kerja_tahun', 'masa_kerja_bulan', 'sk_pejabat', 'sk_nomor',
        'sk_tanggal', 'sk_jabatan_pejabat', 'sk_jabatan_nomor', 'sk_jabatan_tanggal',
        'skp_2_tahun', 'keterangan',
    ];

    protected $casts = [
        'tanggal_lahir'          => 'date',
        'tmt_jabatan'            => 'date',
        'tanggal_masuk'          => 'date',
        'tanggal_pangkat_terakhir' => 'date',
        'sk_tanggal'             => 'date',
        'sk_jabatan_tanggal'     => 'date',
        'masa_kerja_tahun'       => 'integer',
        'masa_kerja_bulan'       => 'integer',
    ];

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'pegawai_id');
    }
}
