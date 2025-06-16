@extends('layouts.app')

@section('title', 'Prediksi Penjualan dan Keuntungan')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Prediksi Penjualan dan Keuntungan</h1>

@if(isset($pythonError) && $pythonError)
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> {{ $pythonError }}
</div>
@endif

{{-- Card for New Rolling Window Table --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tabel Prediksi Penjualan Beruntun (Rolling Window) 2025</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTableRollingWindow" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Bulan Prediksi</th>
                                <th>Prediksi Barang Terjual</th>
                                {{-- START: MODIFIED/ADDED COLUMNS --}}
                                <th>Penjualan Aktual</th>
                                <th>Margin Error</th>
                                {{-- END: MODIFIED/ADDED COLUMNS --}}
                                <th>Prediksi Keuntungan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rollingWindowTableData as $prediction)
                            <tr>
                                <td>{{ $prediction['month'] }}</td>
                                {{-- MODIFIED: Removed multiplication. Data from controller is now correctly scaled. --}}
                                <td>{{ number_format($prediction['predicted_sales'], 0, ',', '.') }}</td>
                                <td>
                                    @if(isset($prediction['actual_sales']))
                                    {{ number_format($prediction['actual_sales'], 0, ',', '.') }}
                                    @else
                                    <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($prediction['margin_error']))
                                    {{ number_format($prediction['margin_error'], 0, ',', '.') }}
                                    @else
                                    <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                {{-- MODIFIED: Removed multiplication --}}
                                <td>Rp {{ number_format($prediction['predicted_profit'], 2, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">Data prediksi rolling window belum tersedia.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Card for New Rolling Window Chart --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                {{-- MODIFIED: Updated chart title --}}
                <h6 class="m-0 font-weight-bold text-primary">Grafik Perbandingan Penjualan Aktual {{ $tahunAktual }}-{{ $tahunPrediksi }} vs Prediksi Rolling Window {{ $tahunPrediksi }}</h6>
            </div>
            <div class="card-body">
                <div style="height: 400px;">
                    <canvas id="chartRollingWindow"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- Original Prediction Table (Hidden as requested) --}}
<div class="row mt-4" style="display: none;">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tabel Prediksi Penjualan Tahun {{ $tahunPrediksi ?? 'N/A' }}</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTablePrediksi" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Prediksi Barang Terjual</th>
                                <th>Prediksi Keuntungan</th>
                                <th>Estimasi Waktu Habis Barang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($predictionsTableData as $prediction)
                            <tr>
                                <td>{{ $prediction['month'] }}</td>
                                <td>{{ number_format($prediction['predicted_sales'] * 100, 0, ',', '.') }}</td>
                                <td>{{ number_format($prediction['predicted_profit'] * 100, 2, ',', '.') }}</td>
                                <td>{{ $prediction['stock_out_date'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">Data prediksi belum tersedia.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Original Yearly Chart (Unchanged) --}}
<div class="row mt-4" style="display: none;">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Grafik Penjualan dan Keuntungan 1 Tahun (Prediksi {{ $tahunPrediksi }} vs Aktual {{ $tahunAktual }})</h6>
            </div>
            <div class="card-body">
                <div style="height: 400px;">
                    <canvas id="chartPredictionYear"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Original Quarterly Charts (Unchanged) --}}
<div class="row mt-4" style="display: none;">
    @php
    $quarters = [
    'quarter1' => 'Q1 (Jan-Mar)', 'quarter2' => 'Q2 (Apr-Jun)',
    'quarter3' => 'Q3 (Jul-Sep)', 'quarter4' => 'Q4 (Okt-Des)',
    ];
    @endphp
    @foreach($quarters as $qKey => $qLabel)
    @if(isset($chartDataQuarterly[$qKey]))
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Grafik Penjualan {{ $qLabel }} Tahun {{ $tahunAktual ?? 'N/A' }}</h6>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="chart{{ ucfirst($qKey) }}"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endforeach
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data passed from controller
        const yearlyChartRawData = @json($yearlyChartData ?? null);
        const chartDataQuarterly = @json($chartDataQuarterly ?? null);
        const rollingWindowChartRawData = @json($rollingWindowChartData ?? null);
        const tahunAktual = @json($tahunAktual ?? 'N/A');

        // Helper function
        function formatRupiah(angka) {
            return 'Rp ' + Number(angka).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // --- NEW CHART: Rolling Window Comparison ---
        if (rollingWindowChartRawData && rollingWindowChartRawData.labels && rollingWindowChartRawData.datasets) {
            const ctxRollingWindow = document.getElementById('chartRollingWindow');
            if (ctxRollingWindow) {
                new Chart(ctxRollingWindow.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: rollingWindowChartRawData.labels,
                        // MODIFIED: Simplified this part. No more multiplication needed.
                        datasets: rollingWindowChartRawData.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Jumlah Barang Terjual'
                                },
                                ticks: {
                                    callback: value => Number(value).toLocaleString('id-ID')
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Bulan'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += Number(context.parsed.y).toLocaleString('id-ID');
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // --- ORIGINAL CHART: Yearly Prediction ---
        if (yearlyChartRawData && yearlyChartRawData.labels && yearlyChartRawData.datasets) {
            const ctxPredictionYear = document.getElementById('chartPredictionYear');
            if (ctxPredictionYear) {
                new Chart(ctxPredictionYear.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: yearlyChartRawData.labels,
                        datasets: (Array.isArray(yearlyChartRawData.datasets) ? yearlyChartRawData.datasets : Object.values(yearlyChartRawData.datasets)).map(dataset => ({
                            ...dataset,
                            // Applying the * 100 multiplication from your original JS
                            data: dataset.label.includes('Prediksi') ? dataset.data.map(v => v * 100) : dataset.data,
                            fill: false,
                            tension: 0.3,
                            borderWidth: 2,
                        }))
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        stacked: false,
                        scales: {
                            ySales: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Jumlah Barang Terjual'
                                },
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: true
                                }
                            },
                            yProfit: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Keuntungan (Rp)'
                                },
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    callback: value => formatRupiah(value)
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Bulan'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            if (context.dataset.yAxisID === 'yProfit') {
                                                label += formatRupiah(context.parsed.y);
                                            } else {
                                                label += Number(context.parsed.y).toLocaleString('id-ID');
                                            }
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // --- ORIGINAL CHARTS: Quarterly ---
        if (chartDataQuarterly) {
            const qColors = {
                quarter1: {
                    border: 'rgba(75, 192, 192, 1)',
                    bg: 'rgba(75, 192, 192, 0.2)'
                },
                quarter2: {
                    border: 'rgba(54, 162, 235, 1)',
                    bg: 'rgba(54, 162, 235, 0.2)'
                },
                quarter3: {
                    border: 'rgba(255, 206, 86, 1)',
                    bg: 'rgba(255, 206, 86, 0.2)'
                },
                quarter4: {
                    border: 'rgba(255, 99, 132, 1)',
                    bg: 'rgba(255, 99, 132, 0.2)'
                },
            };
            for (const qKey in chartDataQuarterly) {
                if (chartDataQuarterly[qKey] && chartDataQuarterly[qKey].labels) {
                    const ctxQ = document.getElementById('chart' + qKey.charAt(0).toUpperCase() + qKey.slice(1));
                    if (ctxQ) {
                        new Chart(ctxQ.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: chartDataQuarterly[qKey].labels,
                                datasets: [{
                                    label: `Penjualan Aktual ${qKey.replace('quarter', 'Q')} - ${tahunAktual}`,
                                    data: chartDataQuarterly[qKey].sales,
                                    borderColor: qColors[qKey]?.border || 'rgba(0,0,0,1)',
                                    backgroundColor: qColors[qKey]?.bg || 'rgba(0,0,0,0.1)',
                                    borderWidth: 1,
                                    fill: true,
                                    tension: 0.1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        title: {
                                            display: true,
                                            text: 'Jumlah Terjual'
                                        },
                                        beginAtZero: true,
                                        ticks: {
                                            callback: value => Number(value).toLocaleString('id-ID')
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: ctx => `${ctx.dataset.label}: ${Number(ctx.parsed.y).toLocaleString('id-ID')}`
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
            }
        }
    });
</script>
@endsection