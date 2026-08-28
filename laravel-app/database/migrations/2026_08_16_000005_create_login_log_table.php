<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login_log')) {
            return; // DB existing: jangan sentuh data
        }

        Schema::create('login_log', function (Blueprint $table) {
            $table->id();
            $table->timestamp('waktu')->nullable();
            $table->string('username', 100)->nullable();
            $table->string('nama_pegawai', 150)->nullable();
            $table->string('role', 50)->nullable();
            $table->enum('status', ['sukses', 'gagal']);
            $table->string('keterangan', 255)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('os', 60)->nullable();
            $table->string('device_type', 30)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('lokasi_text', 255)->nullable();
            $table->boolean('dibaca')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_log');
    }
};
