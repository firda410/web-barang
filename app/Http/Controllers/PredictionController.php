<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\DB;

class PredictionController extends Controller
{
    public function showPrediction()
    {
        // Bagian atas method ini tetap sama...
        $start_date_str = '2025-01-01';
        $end_date_str = '2025-06-30';

        $pythonPath = 'python';
        $scriptPath = base_path('app/Scripts/evaluate_model.py');
        $process = new Process([$pythonPath, $scriptPath, $start_date_str, $end_date_str]);

        try {
            $process->mustRun();
            $output = $process->getOutput();
            $predictions = json_decode($output, true);

            if (isset($predictions['error'])) {
                return view('prediksi.index')->with(['pythonError' => $predictions['error']]);
            }

            $actual_sales_query = DB::table('penjualan')
                ->select(DB::raw("DATE_FORMAT(tanggal_penjualan, '%Y-%m') as bulan"), DB::raw('SUM(jumlah_terjual) as total_penjualan'))
                ->whereBetween('tanggal_penjualan', [$start_date_str, $end_date_str])
                ->groupBy('bulan')
                ->pluck('total_penjualan', 'bulan');

            $avgProfit = DB::table('barang as b')
                ->join('penjualan as p', 'b.kode_barang', '=', 'p.kode_barang')
                ->selectRaw('SUM((b.harga_jual - b.harga_beli) * p.jumlah_terjual) / SUM(p.jumlah_terjual) as avg_profit_per_item')
                ->value('avg_profit_per_item') ?? 0;

            $tableData = [];
            $chartLabels = [];
            $chartActualSales = [];
            $chartPredictedSales = [];

            foreach ($predictions as $pred) {
                if (empty($pred['bulan'])) continue;
                $bulan_key = date('Y-m', strtotime($pred['bulan']));
                $carbonMonth = Carbon::parse($pred['bulan']);
                $aktual = $actual_sales_query[$bulan_key] ?? 0;
                $prediksi = $pred['prediksi_terjual'] ?? 0;
                $mape = ($aktual > 0) ? (abs($aktual - $prediksi) / $aktual) * 100 : 0;

                $tableData[] = [
                    'month' => $carbonMonth->translatedFormat('F Y'),
                    'actual_sales' => $aktual,
                    'predicted_sales' => $prediksi,
                    'mape' => round($mape, 2),
                    'keuntungan_aktual' => $aktual * $avgProfit,
                    'prediksi_keuntungan' => $prediksi * $avgProfit,
                    'selisih_keuntungan' => ($aktual * $avgProfit) - ($prediksi * $avgProfit),
                ];

                $chartLabels[] = $carbonMonth->translatedFormat('F Y');
                $chartActualSales[] = $aktual;
                $chartPredictedSales[] = $prediksi;
            }

            // ====================================================================
            // PERUBAHAN GAYA GRAFIK DI SINI
            // ====================================================================
            $chartData = [
                'labels' => $chartLabels,
                'datasets' => [
                    [
                        'label' => 'Penjualan Aktual',
                        'data' => $chartActualSales,
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'backgroundColor' => 'rgba(54, 162, 235, 0.5)', // Warna fill lebih solid
                        'fill' => true, // <-- Mengaktifkan fill area
                        'tension' => 0.4, // <-- Membuat garis lebih melengkung (smooth)
                        'pointRadius' => 0, // <-- Menghilangkan titik pada garis
                        'pointHoverRadius' => 5,
                    ],
                    [
                        'label' => 'Prediksi Penjualan (SVR)',
                        'data' => $chartPredictedSales,
                        'borderColor' => 'rgba(40, 167, 69, 1)',
                        'backgroundColor' => 'rgba(40, 167, 69, 0.5)', // Warna fill lebih solid
                        'fill' => true, // <-- Mengaktifkan fill area
                        'tension' => 0.4, // <-- Membuat garis lebih melengkung (smooth)
                        'pointRadius' => 0, // <-- Menghilangkan titik pada garis
                        'pointHoverRadius' => 5,
                    ]
                ]
            ];

            return view('prediksi.index', [
                'tableData' => $tableData,
                'chartData' => $chartData,
                'pythonError' => null
            ]);
        } catch (ProcessFailedException $exception) {
            return view('prediksi.index')->with(['pythonError' => $exception->getMessage()]);
        }
    }

    public function showEvaluation()
    {
        // 1. Tentukan periode evaluasi
        $start_date = '2025-01-01';
        // Ambil tanggal hari ini untuk batas akhir data aktual
        $end_date = now()->format('Y-m-d');

        // 2. Panggil skrip evaluasi Python
        $pythonPath = 'python';
        $scriptPath = base_path('app/Scripts/evaluate_model.py');
        $process = new Process([$pythonPath, $scriptPath, $start_date, $end_date]);

        try {
            $process->mustRun();
            $output = $process->getOutput();
            $predictions = json_decode($output, true);

            if (isset($predictions['error'])) {
                return back()->withErrors(['prediction_error' => $predictions['error']]);
            }

            // 3. Ambil data aktual untuk periode yang sama
            $actual_sales_query = DB::table('penjualan')
                ->select(DB::raw("DATE_FORMAT(tanggal_penjualan, '%Y-%m') as bulan"), DB::raw('SUM(jumlah_terjual) as total_penjualan'))
                ->whereBetween('tanggal_penjualan', [$start_date, $end_date])
                ->groupBy('bulan')
                ->pluck('total_penjualan', 'bulan');

            $results = [];
            foreach ($predictions as $pred) {
                $bulan_key = date('Y-m', strtotime($pred['bulan']));
                $aktual = $actual_sales_query[$bulan_key] ?? 0;
                $prediksi = $pred['prediksi_terjual'];

                // 4. Hitung MAPE (%)
                $mape = 0;
                if ($aktual > 0) {
                    $mape = (abs($aktual - $prediksi) / $aktual) * 100;
                }

                $results[] = [
                    'bulan' => Carbon::parse($pred['bulan'])->translatedFormat('F Y'),
                    'penjualan_aktual' => $aktual,
                    'prediksi_terjual' => $prediksi,
                    'mape' => round($mape, 2) // Bulatkan 2 angka di belakang koma
                ];
            }

            return view('prediksi.evaluasi', ['results' => $results]);
        } catch (ProcessFailedException $exception) {
            return back()->withErrors(['prediction_error' => $exception->getMessage()]);
        }
    }

    private function getAdditionalData(array $prediction_months)
    {
        // 1. Hitung rata-rata profit per barang terjual dari seluruh data
        $avgProfit = DB::table('barang as b')
            ->join('penjualan as p', 'b.kode_barang', '=', 'p.kode_barang')
            ->selectRaw('SUM((b.harga_jual - b.harga_beli) * p.jumlah_terjual) / SUM(p.jumlah_terjual) as avg_profit_per_item')
            ->value('avg_profit_per_item');

        // 2. Ambil data penjualan aktual untuk bulan-bulan prediksi
        $start_date = date('Y-m-01', strtotime($prediction_months[0]));
        $end_date = date('Y-m-t', strtotime(end($prediction_months)));

        $actual_sales = DB::table('penjualan')
            ->select(DB::raw("DATE_FORMAT(tanggal_penjualan, '%Y-%m') as bulan"), DB::raw('SUM(jumlah_terjual) as total_penjualan'))
            ->whereBetween('tanggal_penjualan', [$start_date, $end_date])
            ->groupBy('bulan')
            ->pluck('total_penjualan', 'bulan');

        return [
            'avg_profit' => $avgProfit,
            'aktual' => $actual_sales
        ];
    }
}
