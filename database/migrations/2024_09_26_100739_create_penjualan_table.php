<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenjualanTable extends Migration
{
    public function up()
    {
        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 50); // Mengubah barang_id menjadi kode_barang
            $table->integer('jumlah_terjual');
            $table->decimal('total_harga', 15, 2);
            $table->date('tanggal_penjualan');
            $table->timestamps();

            // Foreign key ke tabel barang
            $table->foreign('kode_barang')->references('kode_barang')->on('barang')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('penjualan');
    }
}
