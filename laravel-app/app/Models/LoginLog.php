<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $table = 'login_log';

    public $incrementing = true;

    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'waktu', 'username', 'nama_pegawai', 'role', 'status', 'keterangan',
        'ip_address', 'user_agent', 'browser', 'os', 'device_type',
        'latitude', 'longitude', 'lokasi_text', 'dibaca',
    ];

    protected $casts = [
        'waktu'     => 'datetime',
        'latitude'  => 'float',
        'longitude' => 'float',
        'dibaca'    => 'boolean',
    ];

    public function lokasiLabel(): string
    {
        return $this->lokasi_text
            ?: ($this->latitude !== null && $this->longitude !== null
                ? round($this->latitude, 5) . ', ' . round($this->longitude, 5)
                : 'Lokasi tidak diketahui');
    }

    public function scopeUntukPemirsa($query, ?User $pemirsa = null)
    {
        if ($pemirsa === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($pemirsa->isSuperAdmin()) {
            return $query;
        }

        if ($pemirsa->isAdmin()) {
            return $query->whereRaw(
                "LOWER(role) IN ('kepegawaian','bagian kepegawaian','user')"
            );
        }

        return $query->whereRaw('1 = 0');
    }
}
