<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pegawai')) {
            return; // DB existing: jangan sentuh data
        }
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30)->nullable();
            $table->string('nik', 30)->nullable();
            $table->string('nama')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('agama', 30)->nullable();
            $table->string('suku')->nullable();
            $table->enum('kategori_suku', ['Papua', 'Non-Papua'])->nullable();
            $table->enum('status_kepegawaian', ['CPNS', 'PNS', 'PPPK'])->default('PNS');
            $table->string('pendidikan_terakhir', 50)->nullable();
            $table->string('pendidikan_jurusan', 150)->nullable();
            $table->string('pangkat_terakhir')->nullable();
            $table->string('golongan', 10)->nullable();
            $table->string('jabatan')->nullable();
            $table->string('jenis_jabatan', 30)->default('pelaksana');
            $table->string('status_jabatan', 30)->nullable();
            $table->string('unit_organisasi', 150)->nullable();
            $table->date('tmt_jabatan')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_pangkat_terakhir')->nullable();
            $table->unsignedTinyInteger('masa_kerja_tahun')->nullable();
            $table->unsignedTinyInteger('masa_kerja_bulan')->nullable();
            $table->string('sk_pejabat', 150)->nullable();
            $table->string('sk_nomor', 100)->nullable();
            $table->date('sk_tanggal')->nullable();
            $table->string('sk_jabatan_pejabat', 150)->nullable();
            $table->string('sk_jabatan_nomor', 100)->nullable();
            $table->date('sk_jabatan_tanggal')->nullable();
            $table->string('skp_2_tahun', 20)->nullable();
            $table->text('keterangan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
