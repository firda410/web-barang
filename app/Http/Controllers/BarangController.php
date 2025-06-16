<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Barang;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class BarangController extends Controller
{
    public function index()
    {
        // Fetch the barangs from the database
        $barangs = Barang::all(); // Adjust this line based on your model

        // Return the view with the barangs data
        return view('kelola-barang', compact('barangs')); // Ensure 'barangs' is passed to the view
    }


    public function create()
    {
        return view('barang-tambah'); // View untuk form tambah barang
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'kode_barang' => 'required',
            'nama_barang' => 'required',
            'harga_beli' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'jumlah_awal' => 'required|numeric',
        ]);

        // Simpan data ke database
        Barang::create($request->all());

        return redirect()->route('kelola-barang')->with('success', 'Data barang berhasil ditambahkan');
    }

    public function edit($id)
    {
        $barang = Barang::find($id);
        return view('barang-edit', compact('barang')); // Kirim data barang ke form edit
    }

    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'kode_barang' => 'required',
            'nama_barang' => 'required',
            'harga_beli' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'jumlah_awal' => 'required|numeric',
        ]);

        // Update data di database
        $barang = Barang::find($id);
        $barang->update($request->all());

        return redirect()->route('kelola-barang')->with('success', 'Data barang berhasil diperbarui');
    }

    public function destroy($id)
    {
        $barang = Barang::find($id);
        $barang->delete();
        return redirect()->route('kelola-barang')->with('success', 'Data barang berhasil dihapus');
    }
    public function import(Request $request)
    {
        // Validasi file yang diunggah (maksimum 5MB)
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:5120',
        ]);
    
        try {
            // Ambil file dari request
            $file = $request->file('file');
    
            // Pastikan file valid
            if (!$file->isValid()) {
                throw new \Exception("File yang diunggah tidak valid.");
            }
    
            try {
                // Coba baca file dengan PhpSpreadsheet
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
    
            // Format header yang diharapkan
            $expectedHeaders = ['Kode Barang', 'Nama Barang', 'Keterangan', 'Harga Beli', 'Harga Jual', 'Jumlah Awal'];
            $actualHeaders = array_map('trim', $rows[0]); // Ambil header dari baris pertama
    
            if ($actualHeaders !== $expectedHeaders) {
                throw new \Exception("Format header tidak sesuai. Harap gunakan template yang benar.");
            }
    
            // Mulai proses import data (melewati baris pertama karena header)
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Lewati header
    
                try {
                    // Cek apakah jumlah kolom sesuai
                    if (count($row) < count($expectedHeaders)) {
                        throw new \Exception("Jumlah kolom tidak sesuai pada baris " . ($index + 1));
                    }
    
                    // Ambil data sesuai kolom
                    $kode_barang  = trim($row[0]);
                    $nama_barang  = trim($row[1]);
                    $keterangan   = trim($row[2] ?? '');
                    $harga_beli   = trim($row[3]);
                    $harga_jual   = trim($row[4]);
                    $jumlah_awal  = trim($row[5]);
    
                    // Validasi apakah data kosong
                    if (empty($kode_barang) || empty($nama_barang)) {
                        throw new \Exception("Data kosong ditemukan pada baris " . ($index + 1));
                    }
    
                    // Validasi angka
                    if (!is_numeric($harga_beli) || !is_numeric($harga_jual) || !is_numeric($jumlah_awal)) {
                        throw new \Exception("Kolom harga dan jumlah harus berupa angka pada baris " . ($index + 1));
                    }
    
                    // Simpan data ke database
                    Barang::create([
                        'kode_barang'  => $kode_barang,
                        'nama_barang'  => $nama_barang,
                        'keterangan'   => $keterangan,
                        'harga_beli'   => (float) $harga_beli,
                        'harga_jual'   => (float) $harga_jual,
                        'jumlah_awal'  => (int) $jumlah_awal,
                    ]);
    
                } catch (\Exception $e) {
                    // Log error untuk baris tertentu
                    Log::error("Kesalahan pada baris " . ($index + 1) . ": " . $e->getMessage());
                }
            }
    
            return redirect()->route('kelola-barang')->with('success', 'Data barang berhasil diimport');
    
        } catch (\Exception $e) {
            return redirect()->route('kelola-barang')->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }
    
    
    public function truncate()
    {
        // Hapus semua data tanpa menghapus struktur tabel
        Barang::query()->delete(); // Ini akan menghapus semua data tetapi tidak mengubah constraint
    
        return redirect()->route('kelola-barang.index')->with('success', 'Semua data barang berhasil dihapus.');
    }
}
