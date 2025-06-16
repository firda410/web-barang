<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;
    protected $table = 'penjualan'; // Nama tabel
    protected $fillable = ['kode_barang', 'jumlah_terjual', 'total_harga', 'tanggal_penjualan']; // Pastikan ini sesuai dengan field yang ada di tabel

    // Relasi ke model Barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang'); // Assuming 'kode_barang' is the foreign key
    }
}
