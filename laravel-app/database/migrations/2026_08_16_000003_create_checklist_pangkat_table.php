<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checklist_pangkat')) {
            return;
        }
        Schema::create('checklist_pangkat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pegawai_id');
            $table->string('item_key', 50);
            $table->boolean('checked')->default(false);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['pegawai_id', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_pangkat');
    }
};
