<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;
    protected $table = 'barang'; // Nama tabel
    protected $fillable = ['kode_barang', 'nama_barang', 'keterangan', 'harga_beli', 'harga_jual', 'jumlah_awal'];
}