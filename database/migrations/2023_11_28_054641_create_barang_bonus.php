<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barang_bonus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barang_bonus_id');
            $table->foreign('barang_bonus_id')->references('id')->on('barang_kemasan');
            $table->integer('jumlah_barang_bonus');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_bonus');
    }
};
