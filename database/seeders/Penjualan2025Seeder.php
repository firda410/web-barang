<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

class Penjualan2025Seeder extends Seeder
{
    public function run()
    {
        $this->command->info('Menghapus data penjualan tahun 2025...');
        DB::table('penjualan')->whereYear('tanggal_penjualan', 2025)->delete();

        $products = DB::table('barang')->pluck('harga_jual', 'kode_barang');
        $productCodes = $products->keys()->toArray();

        $this->command->info('Memulai seeder penjualan terkontrol untuk 2025...');

        for ($month = 1; $month <= 6; $month++) {
            $targetMonth = Carbon::create(2025, $month, 1);
            $this->command->info("Memproses bulan: " . $targetMonth->translatedFormat('F Y'));

            $process = new Process(['python', base_path('app/Scripts/predict_single_month.py'), $targetMonth->toDateString()]);
            $process->run();

            $output = json_decode($process->getOutput(), true);
            if (isset($output['error']) || !isset($output['prediksi_terjual'])) {
                $this->command->error("Gagal mendapatkan prediksi untuk bulan " . $targetMonth->translatedFormat('F Y'));
                continue;
            }
            $predictedSales = $output['prediksi_terjual'];

            // ====================================================================
            // LOGIKA BARU YANG LEBIH PASTI
            // ====================================================================
            $targetMAPE = 0;
            $isLossMonth = false;

            // Tentukan bulan April (4) sebagai bulan rugi
            if ($month == 4) {
                $isLossMonth = true;
                // Tetapkan target rugi tipis (misal, 5% di bawah prediksi)
                $targetMAPE = 0.05;
                $this->command->info("  -> (Mode Rugi) Target MAPE: " . ($targetMAPE * 100) . "%");
            } else {
                // Tetapkan target untung dengan MAPE kecil (2% - 8% di atas prediksi)
                $targetMAPE = rand(200, 800) / 10000; // Menghasilkan angka antara 0.02 dan 0.08
                $this->command->info("  -> (Mode Untung) Target MAPE: " . ($targetMAPE * 100) . "%");
            }

            // Hitung penjualan aktual berdasarkan target MAPE yang sudah ditentukan
            if ($isLossMonth) {
                // Jika rugi, aktual = prediksi - (prediksi * MAPE)
                $actualTargetSales = round($predictedSales * (1 - $targetMAPE));
            } else {
                // Jika untung, aktual = prediksi + (prediksi * MAPE)
                $actualTargetSales = round($predictedSales * (1 + $targetMAPE));
            }
            // ====================================================================


            $this->command->info("  -> Prediksi: {$predictedSales}, Target Aktual Dibuat: {$actualTargetSales}");

            // Logika untuk memecah total penjualan bulanan menjadi beberapa record (tetap sama)
            $lastDay = ($month == 6) ? 15 : $targetMonth->endOfMonth()->day;
            $salesForThisMonth = [];
            $accumulatedSales = 0;
            $recordCount = rand(20, 30);

            for ($i = 1; $i <= $recordCount; $i++) {
                $isLastRecord = ($i == $recordCount);
                $remainingSales = $actualTargetSales - $accumulatedSales;

                // Pastikan record terakhir mengisi sisa target, dan yang lain tidak melebihi
                $jumlahTerjual = $isLastRecord ? $remainingSales : rand(1, min($remainingSales, intval($actualTargetSales / ($recordCount / 2))));

                if ($jumlahTerjual <= 0) {
                    if ($isLastRecord) break;
                    else continue;
                }

                $kodeBarang = $productCodes[array_rand($productCodes)];
                $hargaJual = $products[$kodeBarang];
                $totalHarga = $jumlahTerjual * $hargaJual;
                $tanggalPenjualan = Carbon::create(2025, $month, rand(1, $lastDay));

                $salesForThisMonth[] = [
                    'kode_barang' => $kodeBarang,
                    'jumlah_terjual' => $jumlahTerjual,
                    'total_harga' => $totalHarga,
                    'tanggal_penjualan' => $tanggalPenjualan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $accumulatedSales += $jumlahTerjual;
            }

            DB::table('penjualan')->insert($salesForThisMonth);
        }

        $this->command->info('Seeder penjualan tahun 2025 berhasil dijalankan!');
    }
}
