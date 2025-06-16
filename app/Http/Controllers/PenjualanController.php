<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Penjualan;
use App\Models\Barang;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB; // Tambahkan ini
use Carbon\Carbon;

class PenjualanController extends Controller
{
    public function index()
    {
        // Ambil semua data penjualan dari database dengan barang yang terkait
        $penjualans = Penjualan::with('barang')->get(); // Eager load barang
    
        // Kembalikan view dengan data penjualans
        return view('penjualan', compact('penjualans'));
    }

    public function create()
    {
        $barangs = Barang::all(); // Ambil semua data barang untuk dipilih saat menambah penjualan
        return view('penjualan-tambah', compact('barangs'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'kode_barang' => 'required|exists:barang,kode_barang', // Validasi untuk kode_barang yang harus ada di tabel barang
            'jumlah_terjual' => 'required|integer', // Ubah ke integer
            'total_harga' => 'required|numeric',
            'tanggal_penjualan' => 'required|date',
        ]);

        // Simpan data ke database
        Penjualan::create([
            'kode_barang' => $request->kode_barang,
            'jumlah_terjual' => $request->jumlah_terjual,
            'total_harga' => $request->total_harga,
            'tanggal_penjualan' => $request->tanggal_penjualan,
        ]);

        return redirect()->route('penjualan')->with('success', 'Data penjualan berhasil ditambahkan');
    }

    public function edit($id)
    {
        $penjualan = Penjualan::find($id);
        $barangs = Barang::all();
        return view('penjualan-edit', compact('penjualan', 'barangs'));
    }

    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'kode_barang' => 'required|exists:barang,kode_barang', // Validasi kode_barang
            'jumlah_terjual' => 'required|integer', // Ubah ke integer
            'total_harga' => 'required|numeric',
            'tanggal_penjualan' => 'required|date',
        ]);

        // Update data di database
        $penjualan = Penjualan::find($id);
        $penjualan->update($request->all());

        return redirect()->route('penjualan')->with('success', 'Data penjualan berhasil diperbarui');
    }

    public function destroy($id)
    {
        $penjualan = Penjualan::find($id);
        $penjualan->delete();
        return redirect()->route('penjualan')->with('success', 'Data penjualan berhasil dihapus');
    }

public function import(Request $request)
{
    // Validasi file yang diunggah
    $request->validate([
        'file' => 'required|mimes:xlsx,xls|max:2048', // Maksimal ukuran 2MB
    ]);

    try {
        // Ambil file dari request
        $file = $request->file('file');

        // Pastikan file bisa dibaca oleh PhpSpreadsheet
        if (!$file->isValid()) {
            throw new \Exception("File yang diunggah tidak valid.");
        }

        // Coba baca file dengan PhpSpreadsheet
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            throw new \Exception("Format file tidak sesuai atau file rusak.");
        }

        // Ambil worksheet aktif
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Pastikan file tidak kosong
        if (empty($rows) || count($rows) < 2) {
            throw new \Exception("File Excel kosong atau tidak memiliki data.");
        }

        // Pastikan struktur kolom sesuai dengan format yang diharapkan
        $expectedHeaders = ['Kode Barang', 'Jumlah Terjual', 'Total Harga', 'Tanggal Penjualan'];
        $actualHeaders = array_map('trim', $rows[0]); // Ambil header dari baris pertama

        if ($actualHeaders !== $expectedHeaders) {
            throw new \Exception("Format header tidak sesuai. Harap gunakan template yang benar.");
        }

        // Menonaktifkan foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // Mulai proses import data (melewati baris pertama karena header)
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Lewati header

            // Cek apakah jumlah kolom sesuai dengan ekspektasi
            if (count($row) < count($expectedHeaders)) {
                throw new \Exception("Jumlah kolom tidak sesuai pada baris " . ($index + 1));
            }

            // Ambil data sesuai kolom
            $kode_barang = trim($row[0]);
            $jumlah_terjual = trim($row[1]);
            $total_harga = trim($row[2]);
            $tanggal_penjualan = trim($row[3]);

            // Validasi apakah data kosong
            if (empty($kode_barang) || empty($jumlah_terjual) || empty($total_harga) || empty($tanggal_penjualan)) {
                throw new \Exception("Data kosong ditemukan pada baris " . ($index + 1));
            }

            // Validasi angka
            if (!is_numeric($jumlah_terjual) || !is_numeric($total_harga)) {
                throw new \Exception("Kolom jumlah terjual dan total harga harus berupa angka pada baris " . ($index + 1));
            }

            // Validasi format tanggal
            try {
                $tanggal_penjualan = Carbon::parse($tanggal_penjualan);
            } catch (\Exception $e) {
                throw new \Exception("Format tanggal tidak valid pada baris " . ($index + 1));
            }

            // Simpan data ke database
            Penjualan::create([
                'kode_barang' => $kode_barang,
                'jumlah_terjual' => (int) $jumlah_terjual,
                'total_harga' => (float) $total_harga,
                'tanggal_penjualan' => $tanggal_penjualan,
            ]);
        }

        // Mengaktifkan kembali foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        return redirect()->route('penjualan')->with('success', 'Data penjualan berhasil diimport');
    } catch (\Exception $e) {
        return redirect()->route('penjualan')->with('error', 'Gagal mengimport data: ' . $e->getMessage());
    }
}


    

}
