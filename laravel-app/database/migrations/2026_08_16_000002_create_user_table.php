<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user')) {
            return; // DB existing: jangan sentuh akun
        }
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('nama')->nullable();
            $table->string('role', 30)->default('kepegawaian');
            $table->string('password');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
