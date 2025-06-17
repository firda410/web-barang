<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrediksiController extends Controller
{
    public function index()
    {
        return $this->prediksi();
    }

    /**
     * Main prediction method that generates both one-off and rolling window predictions.
     */
    public function prediksi()
    {
        // --- Common Setup ---
        $tahunPrediksi = 2025;
        $tahunAktual = 2024;

        // --- Fetch All Historical Data ---
        $allHistoricalData = Penjualan::select(
            DB::raw('YEAR(tanggal_penjualan) as year'),
            DB::raw('MONTH(tanggal_penjualan) as month'),
            DB::raw('SUM(jumlah_terjual) as total_sales'),
            DB::raw('SUM(total_harga) as total_profit')
        )
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        if ($allHistoricalData->count() < 12) {
            return view(
                'prediksi-error',
                ['message' => 'Tidak cukup data historis untuk melakukan prediksi. Diperlukan minimal 12 bulan data penjualan.']
            );
        }

        // ======================================================================
        // 1. ORIGINAL PREDICTION LOGIC (FOR HIDDEN TABLE & ORIGINAL CHART)
        // ======================================================================
        $originalPredictions = $this->getOriginalOneOffPredictions($allHistoricalData, $tahunPrediksi, $tahunAktual);

        // ======================================================================
        // 2. NEW ROLLING WINDOW PREDICTION LOGIC
        // ======================================================================
        // Fetch actual data for the prediction year (2025) to compare
        $actuals2025 = Penjualan::select(
            DB::raw('MONTH(tanggal_penjualan) as month'),
            DB::raw('SUM(jumlah_terjual) as total_sales')
        )
            ->whereYear('tanggal_penjualan', $tahunPrediksi)
            ->groupBy('month')
            ->get()->keyBy('month');

        $rollingWindowResults = $this->getRollingWindowPredictions($allHistoricalData, $actuals2025, $tahunPrediksi);

        // ======================================================================
        // 3. PREPARE DATA FOR VIEW
        // ======================================================================
        // START: MODIFIED FOR 2024-2025 ACTUALS
        // Combine actual sales from 2024 and 2025 for the chart
        $combinedActualSales = array_fill(0, 18, null); // 12 months 2024 + 6 months 2025
        // Fill 2024 data
        foreach ($originalPredictions['actualSales2024'] as $month => $sales) {
            $combinedActualSales[$month - 1] = $sales;
        }
        // Fill 2025 data
        foreach ($actuals2025 as $month => $data) {
            if (($month - 1 + 12) < 18) { // Ensure it's within Jan-Jun range
                $combinedActualSales[$month - 1 + 12] = (int)$data->total_sales;
            }
        }
        // END: MODIFIED FOR 2024-2025 ACTUALS

        $rollingWindowChartData = $this->prepareRollingWindowChartData(
            $combinedActualSales, // Use combined data
            $rollingWindowResults['predictions'],
            $tahunAktual,
            $tahunPrediksi
        );

        $viewData = array_merge(
            $originalPredictions,
            [
                'rollingWindowTableData' => $rollingWindowResults['tableData'],
                'rollingWindowChartData' => $rollingWindowChartData,
                'pythonError' => null
            ]
        );

        return view('prediksi', $viewData);
    }

    private function predictWithLinearRegression(array $dataPoints, int $periodsToPredict = 1): array
    {
        $n = count($dataPoints);
        if ($n === 0) {
            return array_fill(0, $periodsToPredict, 0);
        }

        $sum_x = array_sum(range(1, $n));
        $sum_y = array_sum($dataPoints);
        $sum_xy = 0;
        $sum_x_sq = 0;

        foreach ($dataPoints as $index => $y) {
            $x = $index + 1;
            $sum_xy += $x * $y;
            $sum_x_sq += $x * $x;
        }

        $denominator = ($n * $sum_x_sq) - ($sum_x * $sum_x);
        if ($denominator == 0) {
            $lastValue = end($dataPoints) ?: 0;
            return array_fill(0, $periodsToPredict, $lastValue);
        }

        $m = (($n * $sum_xy) - ($sum_x * $sum_y)) / $denominator;
        $b = ($sum_y - $m * $sum_x) / $n;

        $predictions = [];
        for ($i = 1; $i <= $periodsToPredict; $i++) {
            $future_x = $n + $i;
            $predictedValue = $m * $future_x + $b;
            $predictions[] = max(0, $predictedValue);
        }

        return $predictions;
    }

    /**
     * Generates predictions using a rolling window approach.
     * IMPROVED: Now uses a seasonal index to create realistic fluctuations.
     */
    private function getRollingWindowPredictions(Collection $initialHistoricalData, Collection $actualsPredictionYear, int $predictionYear): array
    {
        $rollingPredictions = [];
        $tableData = [];
        $liveHistoricalData = collect($initialHistoricalData->toArray());

        // Get all historical data from the year before the prediction window starts, to calculate seasonal index.
        // The first prediction for Jan 2025 will use a window ending Dec 2024. The seasonal base year is 2024.
        $seasonalBaseYear = $predictionYear - 1;
        $seasonalData = $initialHistoricalData->where('year', $seasonalBaseYear)->keyBy('month');
        $seasonalAverage = $seasonalData->pluck('total_sales')->avg();

        // Handle case where there's no data for the seasonal base year to prevent division by zero.
        if ($seasonalAverage == 0) {
            $seasonalAverage = $initialHistoricalData->pluck('total_sales')->avg() ?: 1;
        }

        // Loop for the first 6 months of the prediction year
        for ($month = 1; $month <= 6; $month++) {
            $windowEnd = Carbon::create($predictionYear, $month, 1)->subMonthNoOverflow();
            $windowStart = $windowEnd->copy()->subMonths(11);

            $trainingWindow = $liveHistoricalData->filter(function ($item) use ($windowStart, $windowEnd) {
                $itemDate = Carbon::create($item['year'], $item['month'], 1);
                return $itemDate->between($windowStart, $windowEnd, true);
            });

            // 1. Predict the TRend using Linear Regression
            $trendPredictionArr = $this->predictWithLinearRegression($trainingWindow->pluck('total_sales')->toArray(), 1);
            $trendProfitArr = $this->predictWithLinearRegression($trainingWindow->pluck('total_profit')->toArray(), 1);

            $predictedSalesTrend = (int)($trendPredictionArr[0] ?? 0);
            $predictedProfitTrend = (float)($trendProfitArr[0] ?? 0.0);

            // 2. Calculate the Seasonal Influence
            $lastYearSalesForMonth = $seasonalData->get($month)['total_sales'] ?? $seasonalAverage; // Fallback to average if specific month is missing
            $seasonalIndex = $lastYearSalesForMonth / $seasonalAverage;

            // 3. Apply the seasonal index to the trend prediction
            $finalPredictedSales = (int)($predictedSalesTrend * $seasonalIndex);
            $finalPredictedProfit = (float)($predictedProfitTrend * $seasonalIndex);


            // Store the final prediction for the chart and table
            $rollingPredictions[$month] = [
                'predicted_sales' => $finalPredictedSales,
                'predicted_profit' => $finalPredictedProfit,
            ];

            // Get actuals and calculate margin of error for the table
            $actualSalesForMonth = $actualsPredictionYear->has($month) ? (int)$actualsPredictionYear->get($month)->total_sales : null;
            $marginError = ($actualSalesForMonth !== null) ? abs($actualSalesForMonth - $finalPredictedSales) : null;

            $tableData[] = [
                'month' => Carbon::create($predictionYear, $month)->isoFormat('MMMM YYYY'),
                'predicted_sales' => $finalPredictedSales,  // Already correctly scaled
                'predicted_profit' => $finalPredictedProfit, // Already correctly scaled
                'actual_sales' => $actualSalesForMonth,
                'margin_error' => $marginError,
            ];

            // For the next loop's training data, push the *final fluctuated prediction*
            // to make the next prediction aware of the seasonal adjustment.
            $liveHistoricalData->push([
                'year' => $predictionYear,
                'month' => $month,
                'total_sales' => $finalPredictedSales,
                'total_profit' => $finalPredictedProfit,
            ]);
        }

        return ['predictions' => $rollingPredictions, 'tableData' => $tableData];
    }


    /**
     * Prepares data for the new rolling window comparison chart.
     */
    private function prepareRollingWindowChartData(array $actualSalesCombined, array $rollingPredictions2025, int $tahunAktual, int $tahunPrediksi): array
    {
        $labels = [];
        $currentDate = Carbon::create($tahunAktual, 1, 1);
        for ($i = 0; $i < 18; $i++) {
            $labels[] = $currentDate->format('M Y');
            $currentDate->addMonth();
        }

        // Pad predicted data to align with the 18-month timeline
        $predictedSales2025 = empty($rollingPredictions2025) ? [] : array_column($rollingPredictions2025, 'predicted_sales');
        $predictedData = array_pad(array_fill(0, 12, null), 18, 0); // Start with 12 nulls for 2024
        foreach ($predictedSales2025 as $index => $value) {
            $predictedData[12 + $index] = $value;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                // Updated label and using combined actual data
                ['label' => "Penjualan Aktual {$tahunAktual}-{$tahunPrediksi}", 'data' => $actualSalesCombined, 'borderColor' => '#FFD700', 'backgroundColor' => 'rgba(255, 215, 0, 0.1)', 'tension' => 0.1],
                // MODIFIED: Changed color to green and removed borderDash
                ['label' => "Prediksi Rolling Window {$tahunPrediksi}", 'data' => $predictedData, 'borderColor' => '#28A745', 'backgroundColor' => 'rgba(40, 167, 69, 0.1)', 'tension' => 0.1]
            ]
        ];
    }

    /**
     * Generates one-off predictions for the entire year ahead. (Original logic, adapted for PHP prediction).
     */
    private function getOriginalOneOffPredictions(Collection $historicalData, int $tahunPrediksi, int $tahunAktual): array
    {
        $bulanMap = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

        $dataPenjualanAktual2024 = Penjualan::select(DB::raw('MONTH(tanggal_penjualan) as month'), DB::raw('SUM(jumlah_terjual) as total_sales'), DB::raw('SUM(total_harga) as total_profit'))
            ->whereYear('tanggal_penjualan', $tahunAktual)->groupBy('month')->orderBy('month')->get()->keyBy('month');

        $actualSales2024 = array_fill_keys(range(1, 12), 0);
        $actualProfit2024 = array_fill_keys(range(1, 12), 0.0);
        foreach ($dataPenjualanAktual2024 as $monthNumber => $data) {
            $actualSales2024[$monthNumber] = (int) $data->total_sales;
            $actualProfit2024[$monthNumber] = (float) $data->total_profit;
        }

        $predictedSalesArr = $this->predictWithLinearRegression($historicalData->pluck('total_sales')->toArray(), 12);
        $predictedProfitArr = $this->predictWithLinearRegression($historicalData->pluck('total_profit')->toArray(), 12);

        $predictedSales2025 = array_combine(range(1, 12), $predictedSalesArr);
        $predictedProfit2025 = array_combine(range(1, 12), $predictedProfitArr);

        $predictionsTableData = [];
        foreach ($bulanMap as $monthNumber => $monthName) {
            $predictionsTableData[] = [
                'month' => $monthName,
                'predicted_sales' => (int)($predictedSales2025[$monthNumber] ?? 0),
                'predicted_profit' => (float)($predictedProfit2025[$monthNumber] ?? 0),
                'stock_out_date' => Carbon::create($tahunPrediksi, $monthNumber, 25)->format('d M Y')
            ];
        }

        $shortMonthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $chartDataQuarterly = [
            'quarter1' => ['labels' => array_slice($shortMonthLabels, 0, 3), 'sales' => array_slice(array_values($actualSales2024), 0, 3)],
            'quarter2' => ['labels' => array_slice($shortMonthLabels, 3, 3), 'sales' => array_slice(array_values($actualSales2024), 3, 3)],
            'quarter3' => ['labels' => array_slice($shortMonthLabels, 6, 3), 'sales' => array_slice(array_values($actualSales2024), 6, 3)],
            'quarter4' => ['labels' => array_slice($shortMonthLabels, 9, 3), 'sales' => array_slice(array_values($actualSales2024), 9, 3)]
        ];

        $yearlyChartData = [
            'labels' => array_values($bulanMap),
            'datasets' => [
                ['label' => "Prediksi Barang Terjual {$tahunPrediksi}", 'data' => array_values($predictedSales2025), 'borderColor' => 'green', 'backgroundColor' => 'rgba(0, 128, 0, 0.1)', 'yAxisID' => 'ySales', 'hidden' => false],
                ['label' => "Prediksi Keuntungan {$tahunPrediksi}", 'data' => array_values($predictedProfit2025), 'borderColor' => 'blue', 'backgroundColor' => 'rgba(0, 0, 255, 0.1)', 'yAxisID' => 'yProfit', 'hidden' => true],
                ['label' => "Barang Terjual Aktual {$tahunAktual}", 'data' => array_values($actualSales2024), 'borderColor' => '#FFD700', 'backgroundColor' => 'rgba(255, 215, 0, 0.1)', 'yAxisID' => 'ySales', 'hidden' => false],
                ['label' => "Total Keuntungan Aktual {$tahunAktual}", 'data' => array_values($actualProfit2024), 'borderColor' => 'red', 'backgroundColor' => 'rgba(255, 0, 0, 0.1)', 'yAxisID' => 'yProfit', 'hidden' => true]
            ]
        ];

        return [
            'predictionsTableData' => $predictionsTableData,
            'chartDataQuarterly' => $chartDataQuarterly,
            'yearlyChartData' => $yearlyChartData,
            'actualSales2024' => $actualSales2024,
            'tahunPrediksi' => $tahunPrediksi,
            'tahunAktual' => $tahunAktual,
        ];
    }
}
