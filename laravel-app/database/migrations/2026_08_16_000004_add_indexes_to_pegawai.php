<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cols = array_column(DB::select('SHOW COLUMNS FROM pegawai'), 'Field');
        $pairs = [
            ['idx_pgw_golongan', 'golongan'],
            ['idx_pgw_jk', 'jenis_kelamin'],
            ['idx_pgw_status_kepegawaian', 'status_kepegawaian'],
            ['idx_pgw_kategori_suku', 'kategori_suku'],
            ['idx_pgw_jabatan', 'jabatan'],
            ['idx_pgw_tanggal_lahir', 'tanggal_lahir'],
            ['idx_pgw_tanggal_pangkat', 'tanggal_pangkat_terakhir'],
        ];
        foreach ($pairs as [$indexName, $column]) {
            $exists = DB::select("SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pegawai' AND index_name = ?", [$indexName]);
            if (in_array($column, $cols, true) && (int) $exists[0]->c === 0) {
                DB::statement("ALTER TABLE pegawai ADD INDEX {$indexName} ({$column})");
            }
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX idx_pgw_golongan ON pegawai');
        DB::statement('DROP INDEX idx_pgw_jk ON pegawai');
        DB::statement('DROP INDEX idx_pgw_status_kepegawaian ON pegawai');
        DB::statement('DROP INDEX idx_pgw_kategori_suku ON pegawai');
        DB::statement('DROP INDEX idx_pgw_jabatan ON pegawai');
        DB::statement('DROP INDEX idx_pgw_tanggal_lahir ON pegawai');
        DB::statement('DROP INDEX idx_pgw_tanggal_pangkat ON pegawai');
    }
};
