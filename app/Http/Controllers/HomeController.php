<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Penjualan;
use App\Models\Barang;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Inisialisasi variabel default
        $totalBarangTerjual = 0;
        $totalKeuntungan = 0;
        $totalTransaksi = 0;
        $penjualanBulanan = collect(); // Menggunakan koleksi kosong
        $barangTerlaris = null;

        // Total Barang Terjual
        $totalBarangTerjual = Penjualan::sum('jumlah_terjual');

        // Total Keuntungan
        $totalKeuntungan = Penjualan::sum('total_harga');

        // Total Transaksi
        $totalTransaksi = Penjualan::count();

        // Data untuk Grafik Penjualan Bulanan
        $penjualanBulanan = Penjualan::select(
            DB::raw('MONTH(tanggal_penjualan) as bulan'),
            DB::raw('SUM(jumlah_terjual) as total_terjual')
        )
        ->groupBy('bulan')
        ->orderBy('bulan', 'ASC')
        ->get();

        // Cek apakah ada penjualan
        if ($totalBarangTerjual > 0) {
            // Data untuk Statistik Barang Terlaris
            $barangTerlaris = Penjualan::select('kode_barang', DB::raw('SUM(jumlah_terjual) as total_terjual'))
                ->groupBy('kode_barang')
                ->orderBy('total_terjual', 'DESC')
                ->first();
            // Data untuk Statistik Barang Terlaris
$barangTerlaris = Penjualan::select('kode_barang', DB::raw('SUM(jumlah_terjual) as total_terjual'))
->groupBy('kode_barang')
->orderBy('total_terjual', 'DESC')
->first();


            // Cek apakah ada barang terlaris
            if ($barangTerlaris) {
                $barangTerlaris = Barang::find($barangTerlaris->kode_barang);
            }
        }

        // Kirim data ke view
        return view('home', compact('totalBarangTerjual', 'totalKeuntungan', 'totalTransaksi', 'penjualanBulanan', 'barangTerlaris'));
    }
}
